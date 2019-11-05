<?php

namespace Icinga\Module\Icingadb\Redis;

use Icinga\Module\Icingadb\Model\State;

/**
 * Fetch volatile host or service states from redis.
 */
class VolatileState
{
    /** @var \Redis */
    protected $redis;

    /** @var array Set of keys to sync */
    public static $keys = [
        'attempt',
        'output',
        'long_output',
        'performance_data',
        'check_commandline',
        'execution_time',
        'latency',
        'timeout',
        'last_update',
        'next_check',
        'next_update'
    ];

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
     * Fetch volatile state
     *
     * @param State $state
     *
     * @return $this
     */
    public function fetch(State $state)
    {
        $type = substr($state->getTableName(), 0, -6);

        $json = $this->redis->hGet("icinga:config:state:{$type}", bin2hex($state->{$type . '_id'}));
        if ($json !== false) {
            $data = json_decode($json, true);
            $data = array_intersect_key($data, array_flip(static::$keys));

            $state->setProperties($data);
        }

        return $this;
    }
}
