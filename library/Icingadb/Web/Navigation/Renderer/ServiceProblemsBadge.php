<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Navigation\Renderer;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\ServicestateSummary;

class ServiceProblemsBadge extends ProblemsBadge
{
    use Auth;

    protected function fetchProblemsCount()
    {
        $summary = ServicestateSummary::on($this->getDb())->with('state');
        $this->applyRestrictions($summary);

        return $summary->first()->services_critical_unhandled;
    }

    protected function getUrl()
    {
        return Links::services()
            ->setParams(['service.state.is_problem' => 'y', 'sort' => 'service.state.severity desc']);
    }
}
