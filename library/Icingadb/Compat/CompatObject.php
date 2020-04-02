<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Compat;

use Icinga\Application\Config;
use Icinga\Exception\NotImplementedError;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use InvalidArgumentException;
use ipl\Orm\Model;

use function ipl\Stdlib\get_php_type;

trait CompatObject
{
    use Auth;

    private $defaultLegacyColumns = [
        'flap_detection_enabled' => 'flapping_enabled'
    ];

    /** @var Model $object */
    private $object;

    public function __construct(Model $object)
    {
        $this->object = $object;

        $this->legacyColumns = $this->legacyColumns + $this->defaultLegacyColumns;
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

    public function getActionUrls()
    {
        $actionUrl = $this->object->action_url;

        if ($actionUrl === null) {
            return [];
        }

        return $this->resolveAllStrings(
            MonitoredObject::parseAttributeUrls($actionUrl->action_url)
        );
    }

    /**
     * Get this object's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->object->name;
    }

    public function getNotesUrls()
    {
        $notesUrl = $this->object->notes_url;

        if ($notesUrl === null) {
            return [];
        }

        return $this->resolveAllStrings(
            MonitoredObject::parseAttributeUrls($notesUrl->notes_url)
        );
    }

    public function fetchCustomvars()
    {
        $vars = new CustomvarFilter(
            $this->object->customvar->execute(),
            $this->type,
            $this->getAuth()->getRestrictions('monitoring/blacklist/properties'),
            Config::module('monitoring')->get('security', 'protected_customvars', '')
        );

        $customvars = [];
        foreach ($vars as $row) {
            $customvars[$row->name] = $row->value;
        }

        $this->customvars = $customvars;
        return $this;
    }

    public function __get($name)
    {
        if (isset($this->legacyColumns[$name])) {
            $name = $this->legacyColumns[$name];
        }

        if (is_array($name)) {
            $value = $this->object;

            do {
                $col = array_shift($name);
                $value = $value->$col;
            } while (! empty($name));
        } elseif (property_exists($this, $name)) {
            if ($this->$name === null) {
                $fetchMethod = 'fetch' . ucfirst($name);
                $this->$fetchMethod();
            }

            return $this->$name;
        } else {
            $value = $this->object->$name;
        }

        return $value;
    }

    public function __isset($name)
    {
        return isset($this->object->$name);
    }

    /**
     * @throws NotImplementedError Don't use!
     */
    protected function getDataView()
    {
        throw new NotImplementedError('getDataView() is not supported');
    }
}
