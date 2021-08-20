<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Chart\Donut;
use Icinga\Data\Filter\Filter;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Widget\ServiceStateBadges;
use ipl\Web\Widget\VerticalKeyValue;
use ipl\Html\HtmlString;
use ipl\Web\Filter\QueryString;
use ipl\Web\Widget\Link;

class ServiceStatistics extends ObjectStatistics
{
    protected $summary;

    public function __construct($summary)
    {
        $this->summary = $summary;
    }

    protected function createDonut()
    {
        $donut = (new Donut())
            ->addSlice($this->summary->services_ok, ['class' => 'slice-state-ok'])
            ->addSlice($this->summary->services_warning_handled, ['class' => 'slice-state-warning-handled'])
            ->addSlice($this->summary->services_warning_unhandled, ['class' => 'slice-state-warning'])
            ->addSlice($this->summary->services_critical_handled, ['class' => 'slice-state-critical-handled'])
            ->addSlice($this->summary->services_critical_unhandled, ['class' => 'slice-state-critical'])
            ->addSlice($this->summary->services_unknown_handled, ['class' => 'slice-state-unknown-handled'])
            ->addSlice($this->summary->services_unknown_unhandled, ['class' => 'slice-state-unknown'])
            ->addSlice($this->summary->services_pending, ['class' => 'slice-state-pending']);

        return HtmlString::create($donut->render());
    }

    protected function createTotal()
    {
        $url = Links::services();
        if ($this->hasBaseFilter()) {
            $url->addFilter(Filter::fromQueryString(QueryString::render($this->getBaseFilter())));
        }

        return new Link(
            new VerticalKeyValue(
                tp('Service', 'Services', $this->summary->services_total),
                $this->summary->services_total
            ),
            $url
        );
    }

    protected function createBadges()
    {
        $badges = new ServiceStateBadges($this->summary);
        if ($this->hasBaseFilter()) {
            $badges->setBaseFilter($this->getBaseFilter());
        }

        return $badges;
    }
}
