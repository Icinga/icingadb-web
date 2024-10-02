<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Chart\Donut;

use Icinga\Module\Icingadb\Model\RedundancyGroupParentStateSummary;
use Icinga\Module\Icingadb\Widget\Detail\ObjectStatistics;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Html\HtmlString;

/**
 * Objects statistics
 */
class ObjectsStatistics extends ObjectStatistics
{
    /** @var RedundancyGroupParentStateSummary Objects summary */
    protected $summary;

    public function __construct($summary)
    {
        $this->summary = $summary;
    }

    protected function createDonut(): ValidHtml
    {
        $donut = (new Donut())
            ->addSlice($this->summary->objects_ok, ['class' => 'slice-state-ok'])
            ->addSlice($this->summary->objects_warning_handled, ['class' => 'slice-state-warning-handled'])
            ->addSlice($this->summary->objects_warning_unhandled, ['class' => 'slice-state-warning'])
            ->addSlice($this->summary->objects_problem_handled, ['class' => 'slice-state-critical-handled'])
            ->addSlice($this->summary->objects_problem_unhandled, ['class' => 'slice-state-critical'])
            ->addSlice($this->summary->objects_unknown_handled, ['class' => 'slice-state-unknown-handled'])
            ->addSlice($this->summary->objects_unknown_unhandled, ['class' => 'slice-state-unknown'])
            ->addSlice($this->summary->objects_pending, ['class' => 'slice-state-pending']);

        return HtmlString::create($donut->render());
    }

    protected function createTotal(): ValidHtml
    {
        return Text::create($this->shortenAmount($this->summary->objects_total));
    }

    protected function createBadges(): ValidHtml
    {
        $badges = new ObjectsStateBadges($this->summary);
        if ($this->hasBaseFilter()) {
            $badges->setBaseFilter($this->getBaseFilter());
        }

        return $badges;
    }
}
