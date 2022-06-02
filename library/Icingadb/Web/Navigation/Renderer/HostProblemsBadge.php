<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Navigation\Renderer;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\HoststateSummary;
use ipl\Web\Url;

class HostProblemsBadge extends ProblemsBadge
{
    use Auth;

    protected function fetchProblemsCount()
    {
        $summary = HoststateSummary::on($this->getDb())->with('state');
        $this->applyRestrictions($summary);
        $count = (int) $summary->first()->hosts_down_unhandled;
        if ($count) {
            $this->setTitle(sprintf(
                tp('One unhandled host down', '%d unhandled hosts down', $count),
                $count
            ));
        }

        return $count;
    }

    protected function getUrl(): Url
    {
        return Links::hosts()->setParams(['host.state.is_problem' => 'y', 'sort' => 'host.state.severity desc']);
    }
}
