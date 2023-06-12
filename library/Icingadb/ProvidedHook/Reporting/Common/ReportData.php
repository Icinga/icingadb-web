<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook\Reporting\Common;

use Icinga\Module\Reporting\ReportData as BaseReportData;

class ReportData extends BaseReportData
{
    use SlaTimelines;

    public function getAverages()
    {
        $totals = 0.0;
        $count = 0;
        foreach ($this->getAllTimelines() as $name => $timelines) {
            $totalTime = 0;
            $problemTime = 0;

            /** @var SlaTimeline $timeline */
            foreach ($timelines as $timeline) {
                $totalTime += $timeline->getTotalTime();
                $problemTime += $timeline->getProblemTime();
            }

            if ($totalTime <= 0) {
                continue;
            }

            ++$count;
            $totals += 100 * ($totalTime - $problemTime) / $totalTime;
        }

        if ($count === 0) {
            return [null];
        }

        return [$totals / $count];
    }
}
