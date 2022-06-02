<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Navigation\Renderer;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use ipl\Web\Url;

class ServiceProblemsBadge extends ProblemsBadge
{
    use Auth;

    protected function fetchProblemsCount()
    {
        $summary = ServicestateSummary::on($this->getDb());
        $this->applyRestrictions($summary);
        $count = (int) $summary->first()->services_critical_unhandled;
        if ($count) {
            $this->setTitle(sprintf(
                tp('One unhandled service critical', '%d unhandled services critical', $count),
                $count
            ));
        }

        return $count;
    }

    protected function getUrl(): Url
    {
        return Links::services()
            ->setParams(['service.state.is_problem' => 'y', 'sort' => 'service.state.severity desc']);
    }
}
