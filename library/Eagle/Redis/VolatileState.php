<?php

namespace Icinga\Module\Eagle\Redis;

use ipl\Orm\Model;

/**
 * Base class for helpers to fetch volatile states from Icinga Redis.
 */
abstract class VolatileState
{
    /** @var \Redis */
    protected $redis;

    /** @var array */
    protected $objects = [];

    /**
     * VolatileState constructor.
     *
     * @param \Redis $redis Connection to the Icinga Redis
     */
    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Get the object type for which to fetch volatile states
     *
     * @return string
     */
    abstract public function getType();

    /**
     * Add an object to fetch volatile states for
     *
     * @param Model $object
     *
     * @return $this
     */
    public function add(Model $object)
    {
        $this->objects[bin2hex($object->id)] = $object;

        return $this;
    }

    /**
     * Fetch volatile states
     *
     * @return $this
     */
    public function fetch()
    {
        $keys = array_keys($this->objects);

        if (empty($keys)) {
            return $this;
        }

        $rs = array_combine($keys, $this->redis->hMGet("icinga:state:object:{$this->getType()}", $keys));

        foreach ($rs as $key => $json) {
            if ($json === false) {
                continue;
            }

            $data = json_decode($json, true);

            $this->objects[$key]->state->setProperties($data);
        }

        return $this;
    }
}
