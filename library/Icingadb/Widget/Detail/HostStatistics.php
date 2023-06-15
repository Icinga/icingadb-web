<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Chart\Donut;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Widget\HostStateBadges;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\VerticalKeyValue;
use ipl\Html\HtmlString;
use ipl\Web\Filter\QueryString;
use ipl\Web\Widget\Link;

class HostStatistics extends ObjectStatistics
{
    protected $summary;

    public function __construct($summary)
    {
        $this->summary = $summary;
    }

    protected function createDonut(): ValidHtml
    {
        $donut = (new Donut())
            ->addSlice($this->summary->hosts_up, ['class' => 'slice-state-ok'])
            ->addSlice($this->summary->hosts_down_handled, ['class' => 'slice-state-critical-handled'])
            ->addSlice($this->summary->hosts_down_unhandled, ['class' => 'slice-state-critical'])
            ->addSlice($this->summary->hosts_pending, ['class' => 'slice-state-pending']);

        return HtmlString::create($donut->render());
    }

    protected function createTotal(): ValidHtml
    {
        $url = Links::hosts();
        if ($this->hasBaseFilter()) {
            $url->setQueryString(QueryString::render($this->getBaseFilter()));
        }

        return new Link(
            (new VerticalKeyValue(
                tp('Host', 'Hosts', $this->summary->hosts_total),
                $this->shortenAmount($this->summary->hosts_total)
            ))->setAttribute('title', $this->summary->hosts_total),
            $url
        );
    }

    protected function createBadges(): ValidHtml
    {
        $badges = new HostStateBadges($this->summary);
        if ($this->hasBaseFilter()) {
            $badges->setBaseFilter($this->getBaseFilter());
        }

        return $badges;
    }
}
