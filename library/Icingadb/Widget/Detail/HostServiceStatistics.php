<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\HostLinks;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Widget\HostServiceStateBadges;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\VerticalKeyValue;

class HostServiceStatistics extends ServiceStatistics
{
    protected Host $host;

    public function __construct($summary, Host $host)
    {
        $this->host = $host;
        parent::__construct($summary);
    }

    protected function createTotal(): ValidHtml
    {
        $url = HostLinks::services($this->host);

        return new Link(
            (new VerticalKeyValue(
                tp('Service', 'Services', $this->summary->services_total),
                $this->shortenAmount($this->summary->services_total)
            ))->setAttribute('title', $this->summary->services_total),
            $url
        );
    }

    protected function createBadges(): ValidHtml
    {
        return new HostServiceStateBadges($this->summary, $this->host);
    }
}
