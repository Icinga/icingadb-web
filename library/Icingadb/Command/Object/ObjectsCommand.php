<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Command\Object;

use ArrayIterator;
use Generator;
use Icinga\Module\Icingadb\Command\IcingaCommand;
use InvalidArgumentException;
use ipl\Orm\Model;
use Iterator;
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
     * @var ?Iterator<Model>
     */
    protected $objects;

    /**
     * How many objects to process at once
     *
     * @var ?int
     */
    protected ?int $chunkSize = null;

    /**
     * Set the involved objects
     *
     * @param Iterator<Model> $objects Except generators
     *
     * @return $this
     *
     * @throws InvalidArgumentException If a generator is passed
     */
    public function setObjects(Iterator $objects): self
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
     * @return Iterator
     */
    public function getObjects(): Iterator
    {
        if ($this->objects === null) {
            throw new LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->objects;
    }

    /**
     * Set how many objects to process at once
     *
     * @param ?int $chunkSize
     *
     * @return $this
     */
    public function setChunkSize(?int $chunkSize): static
    {
        $this->chunkSize = $chunkSize;

        return $this;
    }

    /**
     * Get how many objects to process at once
     *
     * @return ?int
     */
    public function getChunkSize(): ?int
    {
        return $this->chunkSize;
    }
}
