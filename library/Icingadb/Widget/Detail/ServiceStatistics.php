<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Chart\Donut;
use Icinga\Module\Icingadb\Common\HostLinks;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Widget\ServiceStateBadges;
use ipl\Html\ValidHtml;
use ipl\Web\Url;
use ipl\Web\Widget\VerticalKeyValue;
use ipl\Html\HtmlString;
use ipl\Web\Widget\Link;

class ServiceStatistics extends ObjectStatistics
{
    protected $summary;

    /** @var ?Url */
    protected ?Url $url;

    public function __construct($summary)
    {
        $this->summary = $summary;
    }

    /**
     * Return the URL pointing to all matching services.
     *
     * If not set, the URL of the services overview is returned as fallback.
     *
     * @return Url
     */
    public function getUrl(): Url
    {
        return $this->url ?? Links::services();
    }

    /**
     * Set the URL pointing to all matching services.
     *
     * @param Url $url The URL to set.
     *
     * @return $this
     */
    public function setUrl(Url $url): self
    {
        $this->url = $url;

        return $this;
    }

    protected function createDonut(): ValidHtml
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

    protected function createTotal(): ValidHtml
    {
        $url = $this->getUrl();
        if ($this->hasBaseFilter()) {
            $url->setFilter($this->getBaseFilter());
        }

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
        $badges = new ServiceStateBadges($this->summary);
        if ($this->hasBaseFilter()) {
            $badges->setBaseFilter($this->getBaseFilter());
        }

        return $badges->setUrl($this->getUrl());
    }
}
