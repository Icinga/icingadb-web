<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Compat;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\CustomvarFlat;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\Servicegroup;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use InvalidArgumentException;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Stdlib\Filter;
use LogicException;

use function ipl\Stdlib\get_php_type;

trait CompatObject
{
    use Auth;
    use Database;

    /** @var array Non-obscured custom variables */
    protected $rawCustomvars;

    /** @var array Non-obscured host custom variables */
    protected $rawHostCustomvars;

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

    protected function fetchRawHostCustomvars(): self
    {
        if ($this->rawHostCustomvars !== null) {
            return $this;
        }

        $vars = $this->object->host->customvar->execute();

        $customVars = [];
        foreach ($vars as $row) {
            $customVars[$row->name] = $row->value;
        }

        $this->rawHostCustomvars = $customVars;

        return $this;
    }

    public function fetchComments()
    {
        $this->comments = [];

        return $this;
    }

    public function fetchContactgroups()
    {
        $this->contactgroups = [];

        return $this;
    }

    public function fetchContacts()
    {
        $this->contacts = [];

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

    public function fetchHostVariables()
    {
        if (isset($this->hostVariables)) {
            return $this;
        }

        $this->hostVariables = [];
        foreach ($this->object->customvar as $customvar) {
            $this->hostVariables[strtolower($customvar->name)] = json_decode($customvar->value);
        }

        return $this;
    }

    public function fetchServiceVariables()
    {
        if (isset($this->serviceVariables)) {
            return $this;
        }

        $this->serviceVariables = [];
        foreach ($this->object->customvar as $customvar) {
            $this->serviceVariables[strtolower($customvar->name)] = json_decode($customvar->value);
        }

        return $this;
    }

    public function fetchDowntimes()
    {
        $this->downtimes = [];

        return $this;
    }

    public function fetchEventhistory()
    {
        $this->eventhistory = [];

        return $this;
    }

    public function fetchHostgroups()
    {
        if ($this->type === self::TYPE_HOST) {
            $hostname = $this->object->name;
            $hostgroupQuery = clone $this->object->hostgroup;
        } else {
            $hostname = $this->object->host->name;
            $hostgroupQuery = clone $this->object->host->hostgroup;
        }

        $hostgroupQuery
            ->columns(['name', 'display_name'])
            ->filter(Filter::equal('host.name', $hostname));

        /** @var Query $hostgroupQuery */
        $this->hostgroups = [];
        foreach ($hostgroupQuery as $hostgroup) {
            $this->hostgroups[$hostgroup->name] = $hostgroup->display_name;
        }

        return $this;
    }

    public function fetchServicegroups()
    {
        if ($this->type === self::TYPE_HOST) {
            $hostname = $this->object->name;
            $query = Servicegroup::on($this->getDb());
        } else {
            $hostname = $this->object->host->name;
            $query = (clone $this->object->servicegroup);
        }

        $query
            ->columns(['name', 'display_name'])
            ->filter(Filter::equal('host.name', $hostname));

        if ($this->type === self::TYPE_SERVICE) {
            $query->filter(Filter::equal('service.name', $this->object->name));
        }

        $this->servicegroups = [];
        foreach ($query as $serviceGroup) {
            $this->servicegroups[$serviceGroup->name] = $serviceGroup->display_name;
        }

        return $this;
    }

    public function fetchStats()
    {
        $query = ServicestateSummary::on($this->getDb());

        if ($this->type === self::TYPE_HOST) {
            $query->filter(Filter::equal('host.name', $this->object->name));
        } else {
            $query->filter(Filter::all(
                Filter::equal('host.name', $this->object->host->name),
                Filter::equal('service.name', $this->object->name)
            ));
        }

        $result = $query->first();

        $this->stats = (object) [
            'services_total' => $result->services_total,
            'services_ok' => $result->services_ok,
            'services_critical' => $result->services_critical_handled + $result->services_critical_unhandled,
            'services_critical_unhandled' => $result->services_critical_unhandled,
            'services_critical_handled' => $result->services_critical_handled,
            'services_warning' => $result->services_warning_handled + $result->services_warning_unhandled,
            'services_warning_unhandled' => $result->services_warning_unhandled,
            'services_warning_handled' => $result->services_warning_handled,
            'services_unknown' => $result->services_unknown_handled + $result->services_unknown_unhandled,
            'services_unknown_unhandled' => $result->services_unknown_unhandled,
            'services_unknown_handled' => $result->services_unknown_handled,
            'services_pending' => $result->services_pending
        ];

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
                    $customvars = $this->fetchRawHostCustomvars()->rawHostCustomvars;
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
