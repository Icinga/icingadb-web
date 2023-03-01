<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Object;

use Icinga\Module\Icingadb\Command\IcingaCommand;
use ipl\Orm\Model;
use Traversable;

/**
 * Base class for commands that involve monitored objects, i.e. hosts or services
 */
abstract class ObjectsCommand extends IcingaCommand
{
    /**
     * Involved objects
     *
     * @var Traversable<Model>
     */
    protected $objects;

    /**
     * Set the involved objects
     *
     * @param Traversable<Model> $objects
     *
     * @return $this
     */
    public function setObjects(Traversable $objects): self
    {
        $this->objects = $objects;

        return $this;
    }

    /**
     * Get the involved objects
     *
     * @return Traversable
     */
    public function getObjects(): Traversable
    {
        if ($this->objects === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->objects;
    }
}
