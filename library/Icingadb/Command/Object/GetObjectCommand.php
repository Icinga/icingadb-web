<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Object;

use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use LogicException;

class GetObjectCommand extends ObjectCommand
{
    /** @var array */
    protected $attributes;

    /**
     * Get the object name used in the Icinga 2 API
     *
     * @return string
     */
    public function getObjectName()
    {
        switch (true) {
            case $this->object instanceof Service:
                return $this->object->host->name . '!' . $this->object->name;
            default:
                return $this->object->name;
        }
    }

    /**
     * Get the sub-route of the endpoint for this object
     *
     * @return string
     */
    public function getObjectPluralType()
    {
        switch (true) {
            case $this->object instanceof Host:
                return 'hosts';
            case $this->object instanceof Service:
                return 'services';
            default:
                throw new LogicException(sprintf('Invalid object type %s provided', get_class($this->object)));
        }
    }

    /**
     * Get the attributes to query
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set the attributes to query
     *
     * @param array $attributes
     *
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }
}
