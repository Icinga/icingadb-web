<?php

namespace Icinga\Module\Icingadb\Web\Navigation\Renderer;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\ServicestateSummary;

class ServiceProblemsBadge extends ProblemsBadge
{
    protected function fetchProblemsCount()
    {
        return ServicestateSummary::on($this->getDb())->with('state')->first()->services_critical_unhandled;
    }

    protected function getUrl()
    {
        return Links::services()
            ->setParams(['service.state.is_problem' => 'y', 'sort' => 'service.state.severity desc']);
    }
}
