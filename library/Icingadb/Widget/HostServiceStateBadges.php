<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\HostLinks;
use Icinga\Module\Icingadb\Model\Host;
use ipl\Web\Url;

class HostServiceStateBadges extends ServiceStateBadges
{
    protected Host $host;

    public function __construct($summary, Host $host)
    {
        $this->host = $host;
        parent::__construct($summary);
    }

    protected function getBaseUrl(): Url
    {
        return HostLinks::services($this->host);
    }
}
