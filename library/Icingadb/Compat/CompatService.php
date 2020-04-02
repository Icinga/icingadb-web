<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Compat;

use Icinga\Module\Monitoring\Object\Service;

class CompatService extends Service
{
    use CompatObject;

    private $legacyColumns = [
        'host_name' => ['host', 'name']
    ];

    /**
     * Get this service's host
     *
     * @return CompatHost
     */
    public function getHost()
    {
        if ($this->host === null) {
            $this->host = new CompatHost($this->object->host);
        }

        return $this->host;
    }
}
