<?php

namespace Icinga\Module\Eagle\Compat;

/**
 * Class CompatService
 * @package Icinga\Module\Eagle\Compat
 */
class CompatService extends CompatObject
{
    protected $type = self::TYPE_SERVICE;

    /**
     * Get this service's host
     *
     * @return CompatHost
     */
    public function getHost()
    {
        return new CompatHost($this->host);
    }
}
