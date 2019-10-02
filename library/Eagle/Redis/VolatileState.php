<?php

namespace Icinga\Module\Eagle\Redis;

use ipl\Orm\Model;

/**
 * Fetch volatile host or service states from redis.
 */
class VolatileState
{
    /** @var \Redis */
    protected $redis;

    /** @var string */
    protected $type;

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
     * Add an object to fetch volatile states for
     *
     * @param Model $object
     *
     * @return $this
     */
    public function add(Model $object)
    {
        $this->objects[bin2hex($object->id)] = $object;

        if ($this->type === null) {
            $this->type = $object->getTableName();
        }

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

        $rs = array_combine($keys, $this->redis->hMGet("icinga:config:state:{$this->type}", $keys));

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
