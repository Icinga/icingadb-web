<?php

namespace Icinga\Module\Eagle\Compat;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use ipl\Orm\Model;

/**
 * Class CompatObject
 * @package Icinga\Module\Eagle\Compat
 */
class CompatObject extends MonitoredObject
{
    private $legacyColumns = ['flap_detection_enabled' => 'flapping_enabled'];

    /** @var Model $object */
    private $object;

    public function __construct(Model $object)
    {
        $this->object = $object;
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
