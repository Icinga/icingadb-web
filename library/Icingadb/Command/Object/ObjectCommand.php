<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Object;

use Icinga\Module\Icingadb\Command\IcingaCommand;
use ipl\Orm\Model;

/**
 * Base class for commands that involve a monitored object, i.e. a host or service
 */
abstract class ObjectCommand extends IcingaCommand
{
    /**
     * Involved object
     *
     * @var Model
     */
    protected $object;

    /**
     * Set the involved object
     *
     * @param   Model $object
     *
     * @return  $this
     */
    public function setObject(Model $object): self
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Get the involved object
     *
     * @return Model
     */
    public function getObject(): Model
    {
        if ($this->object === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->object;
    }
}
