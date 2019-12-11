<?php

namespace Icinga\Module\Icingadb\Web\Navigation\Renderer;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\HoststateSummary;

class HostProblemsBadge extends ProblemsBadge
{
    protected function fetchProblemsCount()
    {
        return HoststateSummary::on($this->getDb())->with('state')->first()->hosts_down_unhandled;
    }

    protected function getUrl()
    {
        return Links::hosts()->setParams(['host.state.is_problem' => 'y', 'sort' => 'host.state.severity desc']);
    }
}
