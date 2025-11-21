<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Chart\Donut;
use Icinga\Module\Icingadb\Widget\Detail\ObjectStatistics;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Html\HtmlString;

/**
 * Dependency node statistics
 */
class DependencyNodeStatistics extends ObjectStatistics
{
    protected $summary;

    public function __construct($summary)
    {
        $this->summary = $summary;
    }

    protected function createDonut(): ValidHtml
    {
        $donut = (new Donut())
            ->addSlice($this->summary->nodes_ok, ['class' => 'slice-state-ok'])
            ->addSlice($this->summary->nodes_warning_handled, ['class' => 'slice-state-warning-handled'])
            ->addSlice($this->summary->nodes_warning_unhandled, ['class' => 'slice-state-warning'])
            ->addSlice($this->summary->nodes_problem_handled, ['class' => 'slice-state-critical-handled'])
            ->addSlice($this->summary->nodes_problem_unhandled, ['class' => 'slice-state-critical'])
            ->addSlice($this->summary->nodes_unknown_handled, ['class' => 'slice-state-unknown-handled'])
            ->addSlice($this->summary->nodes_unknown_unhandled, ['class' => 'slice-state-unknown'])
            ->addSlice($this->summary->nodes_pending, ['class' => 'slice-state-pending']);

        return HtmlString::create($donut->render());
    }

    protected function createTotal(): ValidHtml
    {
        return Text::create($this->shortenAmount($this->summary->nodes_total));
    }

    protected function createBadges(): ValidHtml
    {
        return new DependencyNodeStateBadges($this->summary);
    }
}
