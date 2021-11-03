<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Compat;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Model\CustomvarFlat;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use InvalidArgumentException;
use ipl\Orm\Model;
use LogicException;

use function ipl\Stdlib\get_php_type;

trait CompatObject
{
    use Auth;

    /** @var array Non-obscured custom variables */
    protected $rawCustomvars;

    /** @var Model $object */
    private $object;

    public function __construct(Model $object)
    {
        $this->object = $object;
    }

    public static function fromModel(Model $object)
    {
        switch (true) {
            case $object instanceof Host:
                return new CompatHost($object);
            case $object instanceof Service:
                return new CompatService($object);
            default:
                throw new InvalidArgumentException(sprintf(
                    'Host or Service Model instance expected, got "%s" instead.',
                    get_php_type($object)
                ));
        }
    }

    /**
     * Get this object's name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->object->name;
    }

    public function fetch(): bool
    {
        return true;
    }

    protected function fetchRawCustomvars(): self
    {
        if ($this->rawCustomvars !== null) {
            return $this;
        }

        $vars = $this->object->customvar->execute();

        $customVars = [];
        foreach ($vars as $row) {
            $customVars[$row->name] = $row->value;
        }

        $this->rawCustomvars = $customVars;

        return $this;
    }

    public function fetchCustomvars(): self
    {
        if ($this->customvars !== null) {
            return $this;
        }

        $this->customvars = (new CustomvarFlat())->unflattenVars($this->object->customvar_flat);

        return $this;
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            if ($this->$name === null) {
                $fetchMethod = 'fetch' . ucfirst($name);
                $this->$fetchMethod();
            }

            return $this->$name;
        }

        if (preg_match('/^_(host|service)_(.+)/i', $name, $matches)) {
            switch (strtolower($matches[1])) {
                case $this->type:
                    $customvars = $this->fetchRawCustomvars()->rawCustomvars;
                    break;
                case self::TYPE_HOST:
                    $customvars = $this->getHost()->fetchRawCustomvars()->rawCustomvars;
                    break;
                case self::TYPE_SERVICE:
                    throw new LogicException('Cannot fetch service custom variables for non-service objects');
            }

            $variableName = strtolower($matches[2]);
            if (isset($customvars[$variableName])) {
                return $customvars[$variableName];
            }

            return null; // Unknown custom variables MUST NOT throw an error
        }

        if (! array_key_exists($name, $this->legacyColumns) && ! $this->object->hasProperty($name)) {
            if (isset($this->customvars[$name])) {
                return $this->customvars[$name];
            }

            if (substr($name, 0, strlen($this->prefix)) !== $this->prefix) {
                $name = $this->prefix . $name;
            }
        }

        if (array_key_exists($name, $this->legacyColumns)) {
            $opts = $this->legacyColumns[$name];
            if ($opts === null) {
                return null;
            }

            $path = $opts['path'];
            $value = null;

            if (! empty($path)) {
                $value = $this->object;

                do {
                    $col = array_shift($path);
                    $value = $value->$col;
                } while (! empty($path) && $value !== null);
            }

            if (isset($opts['type'])) {
                $method = 'get' . ucfirst($opts['type']) . 'Type';
                $value = $this->$method($value);
            }

            return $value;
        }

        return $this->object->$name;
    }

    public function __isset($name)
    {
        if (property_exists($this, $name)) {
            return isset($this->$name);
        }

        if (isset($this->legacyColumns[$name]) || isset($this->object->$name)) {
            return true;
        }

        return false;
    }

    /**
     * @throws NotImplementedError Don't use!
     */
    protected function getDataView()
    {
        throw new NotImplementedError('getDataView() is not supported');
    }

    /**
     * Get the bool type of the given value as an int
     *
     * @param bool|string $value
     *
     * @return ?int
     */
    private function getBoolType($value)
    {
        switch ($value) {
            case false:
                return 0;
            case true:
                return 1;
            case 'sticky':
                return 2;
        }
    }
}
