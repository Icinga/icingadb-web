<?php

namespace Icinga\Module\Icingadb\Compat;

use Countable;
use IteratorAggregate;
use Traversable;

class CompatObjects implements IteratorAggregate, Countable
{
    protected $query;
    protected $compatClass;

    public function __construct($query, $compatClass)
    {
        $this->query = $query;
        $this->compatClass = $compatClass;
    }

    public function getIterator()
    {
        foreach ($this->query as $object) {
            yield new $this->compatClass($object);
        }
    }

    public function count()
    {
        if ($this->query instanceof Traversable) {
            return $this->query->count();
        }

        return count($this->query);
    }
}
