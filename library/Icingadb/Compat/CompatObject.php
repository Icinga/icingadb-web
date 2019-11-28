<?php

namespace Icinga\Module\Icingadb\Compat;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use InvalidArgumentException;
use ipl\Orm\Model;
use function ipl\Stdlib\get_php_type;

/**
 * Class CompatObject
 * @package Icinga\Module\Icingadb\Compat
 */
abstract class CompatObject extends MonitoredObject
{
    private $legacyColumns = ['flap_detection_enabled' => 'flapping_enabled'];

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

    public function __isset($name)
    {
        return isset($this->object->$name);
    }

    public function __get($name)
    {
        if (isset($this->legacyColumns[$name])) {
            $name = $this->legacyColumns[$name];
        }

        return $this->object->$name;
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

    /**
     * @throws NotImplementedError Don't use!
     */
    protected function getDataView()
    {
        throw new NotImplementedError('getDataView() is not supported');
    }

    /**
     * @throws NotImplementedError Don't use!
     */
    public function getNotesUrls()
    {
        throw new NotImplementedError('getNotesUrls() is not supported');
    }
}
