<?php

namespace Icinga\Module\Icingadb\Compat;

use Icinga\Module\Monitoring\Object\Service;

class CompatService extends Service
{
    use CompatObject;

    /**
     * Get this service's host
     *
     * @return CompatHost
     */
    public function getHost()
    {
        return new CompatHost($this->object->host);
    }
}
