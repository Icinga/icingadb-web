<?php

namespace Icinga\Module\Eagle\Redis;

/**
 * Fetch volatile host states from redis.
 */
class VolatileHostState extends VolatileState
{
    public function getType()
    {
        return 'host';
    }
}
