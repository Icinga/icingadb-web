<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Object;

use ArrayIterator;
use Generator;
use Icinga\Module\Icingadb\Command\IcingaCommand;
use InvalidArgumentException;
use ipl\Orm\Model;
use LogicException;
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
     * @param Traversable<Model> $objects Except generators
     *
     * @return $this
     *
     * @throws InvalidArgumentException If a generator is passed
     */
    public function setObjects(Traversable $objects): self
    {
        if ($objects instanceof Generator) {
            throw new InvalidArgumentException('Generators are not supported');
        }

        $this->objects = $objects;

        return $this;
    }

    /**
     * Set the involved object
     *
     * @param Model $object
     *
     * @return $this
     *
     * @deprecated Use setObjects() instead
     */
    public function setObject(Model $object): self
    {
        return $this->setObjects(new ArrayIterator([$object]));
    }

    /**
     * Get the involved objects
     *
     * @return Traversable
     */
    public function getObjects(): Traversable
    {
        if ($this->objects === null) {
            throw new LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->objects;
    }
}
