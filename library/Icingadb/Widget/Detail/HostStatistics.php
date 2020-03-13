<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Chart\Donut;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Widget\HostStateBadges;
use Icinga\Module\Icingadb\Widget\VerticalKeyValue;
use ipl\Html\HtmlString;
use ipl\Web\Widget\Link;

class HostStatistics extends ObjectStatistics
{
    protected $summary;

    public function __construct($summary)
    {
        $this->summary = $summary;
    }

    protected function createDonut()
    {
        $donut = (new Donut())
            ->addSlice($this->summary->hosts_up, ['class' => 'slice-state-ok'])
            ->addSlice($this->summary->hosts_down_handled, ['class' => 'slice-state-critical-handled'])
            ->addSlice($this->summary->hosts_down_unhandled, ['class' => 'slice-state-critical'])
            ->addSlice($this->summary->hosts_pending, ['class' => 'slice-state-pending']);

        return HtmlString::create($donut->render());
    }

    protected function createTotal()
    {
        $url = Links::hosts();
        if ($this->hasBaseFilter()) {
            $url->addFilter($this->getBaseFilter());
        }

        return new Link(
            new VerticalKeyValue(
                mtp('icingadb', 'Host', 'Hosts', $this->summary->hosts_total),
                $this->summary->hosts_total
            ),
            $url
        );
    }

    protected function createBadges()
    {
        $badges = new HostStateBadges($this->summary);
        if ($this->hasBaseFilter()) {
            $badges->setBaseFilter($this->getBaseFilter());
        }

        return $badges;
    }
}
