<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook\Reporting\Common;

use DateInterval;
use DatePeriod;
use DateTime;
use Generator;
use Icinga\Module\Icingadb\Model\HostSlaHistory;
use Icinga\Module\Icingadb\Model\HostState;
use Icinga\Module\Icingadb\Model\ServiceSlaHistory;
use Icinga\Module\Icingadb\Model\ServiceState;
use Icinga\Module\Icingadb\Model\SlaHistoryState;
use Icinga\Module\Icingadb\ProvidedHook\Reporting\HostSlaReport;
use ipl\Orm\Query;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Filter\Rule;
use ipl\Stdlib\Str;
use ipl\Web\Filter\QueryString;

trait SlaReportUtils
{
    public function getReportType(): string
    {
        return $this instanceof HostSlaReport ? 'host' : 'service';
    }

    /**
     * Fetch SLA according to specified time range and filter
     *
     * @param DateTime $start
     * @param DateTime $end
     * @param Rule|null $filter
     *
     * @return Query
     */
    protected function fetchSla(DateTime $start, DateTime $end, Rule $filter = null, array $timeranges = []): Query
    {
        if ($this->getReportType() === 'host') {
            $query = HostSlaHistory::on($this->getDb(), $timeranges);
        } else {
            $query = ServiceSlaHistory::on($this->getDb(), $timeranges);
        }

        $unions = $query->getUnions();
        $slaDowntimeFilter = Filter::all(
            Filter::lessThan('sla_history_downtime.downtime_start', $end),
            Filter::greaterThanOrEqual('sla_history_downtime.downtime_end', $start)
        );

        $unions[0]
            ->filter($slaDowntimeFilter)
            ->columns(
                array_merge(
                    $unions[0]->getColumns(),
                    [
                        'event_time' => new Expression(
                            sprintf(
                                'GREATEST(%s_sla_history_downtime.downtime_start, %s)',
                                $this->getReportType(),
                                $start->format('Uv')
                            )
                        )
                    ]
                )
            );

        $unions[1]
            ->filter($slaDowntimeFilter)
            ->filter(Filter::lessThan('sla_history_downtime.downtime_end', $end));

        $unions[2]->filter(Filter::all(
            Filter::greaterThan('sla_history_state.event_time', $start),
            Filter::lessThan('sla_history_state.event_time', $end)
        ));

        if ($filter !== null) {
            foreach ($unions as $union) {
                $union->filter($filter);
            }
        }

        if (method_exists($this, 'applyRestrictions')) {
            $this->applyRestrictions($query);
        }

        return $query;
    }

    protected function fetchReportData(DateTime $start, DateTime $end, array $config = null)
    {
        $rd = $this->createReportData();

        $filter = trim((string) $config['filter']) ?: '*';
        $filter = $filter !== '*' ? QueryString::parse($filter) : null;
        $isHostQuery = $this->getReportType() === 'host';

        if (isset($config['breakdown']) && $config['breakdown'] !== 'none') {
            switch ($config['breakdown']) {
                case 'day':
                    $interval = new DateInterval('P1D');
                    $format = 'Y-m-d';
                    $boundary = 'tomorrow midnight';

                    break;
                case 'week':
                    $interval = new DateInterval('P1W');
                    $format = 'Y-\WW';
                    $boundary = 'monday next week midnight';

                    break;
                case 'month':
                    $interval = new DateInterval('P1M');
                    $format = 'Y-m';
                    $boundary = 'first day of next month midnight';

                    break;
            }

            $dimensions = $rd->getDimensions();
            $dimensions[] = ucfirst($config['breakdown']);
            $rd->setDimensions($dimensions);

            $slaWithBreakdown = true;
            $timeranges = [];
            foreach ($this->yieldTimerange($start, $end, $interval, $boundary) as list($begin, $endTime)) {
                $timerange = (object) [];
                $timerange->start = $begin;
                $timerange->end = $endTime;

                $timeranges[] = $timerange;
            }
        } else {
            $timeranges[] = (object) ['start' => $start, 'end' => $end];
            $slaWithBreakdown = false;
        }

        $timelines = [];
        $rows = [];
        $objectInfo = [];
        foreach ($this->fetchSla($start, $end, $filter, $timeranges) as $row) {
            $key = $isHostQuery ? bin2hex($row->host_id) : bin2hex($row->service_id);
            foreach ($timeranges as $timerange) {
                $time = (int) $row->event_time;
                if ($time >= (int) $timerange->start->format('Uv') && $time <= (int) $timerange->end->format('Uv')) {
                    $start = $timerange->start;
                    $end = $timerange->end;

                    break;
                }
            }

            if (isset($timelines[$key])) {
                $timeline = $timelines[$key];
            } else {
                $timeline = new SlaTimeline($start, $end, $this->getReportType());
                if (isset($objectInfo[$key]->lastState)) {
                    // No need to retrieve the initial hard state from the database, as we have already cached
                    // the last hard state from the previous timeline interval of this object.
                    $initialHardState = $objectInfo[$key]->lastState;
                } else {
                    $serviceId = ! $isHostQuery ? $row->service_id : null;
                    list($initialHardState, $isBefore) = $this->fetchInitialHardState(
                        $start,
                        $row->host_id,
                        $serviceId
                    );

                    // Cache whether the current initial hard state retrieved from the database
                    // is from before the beginning of this timeline interval.
                    $objectInfo[$key] = (object) ['isFromBeforeInterval' => $isBefore];
                }

                $timeline->setInitialHardState($initialHardState);
            }

            $timeline->addEvent(
                (object) [
                    'type'              => $row->event_type,
                    'time'              => (int)$row->event_time,
                    'hardState'         => $row->hard_state === null ? null : (int)$row->hard_state,
                    'previousHardState' => $row->previous_hard_state === null ? null : (int)$row->previous_hard_state,
                ]
            );

            if ($row->hard_state !== null) {
                // Cache the current object last hard_state, which may be used as the initial hard state for
                // the next timeline interval.
                $objectInfo[$key]->lastState = (int) $row->hard_state;
                // Obviously, this state can always be from before the beginning of the next timeline interval
                $objectInfo[$key]->isFromBeforeInterval = true;
            }

            if ($row->event_type === SlaTimeline::END_RESULT) {
                $report = (object) [];
                $report->sla = $timeline->getResult();

                unset($timelines[$key]);

                $info = $objectInfo[$key];
                if (
                    $slaWithBreakdown
                    && (
                        $report->sla === null
                        || (
                            ! $info->isFromBeforeInterval
                            && count($timeline) <= 1
                        )
                    )
                ) {
                    // This is only the case when the object doesn't have any history events in a given
                    // timeframe or the timeline contains only the fake end event of the specified timeframe.
                    // Either way, we have to skip this timeline.
                    continue;
                }

                $rd->addTimeline($key, $timeline);

                $report->display_name = $row->display_name;
                if (! $isHostQuery) {
                    $report->host_display_name = $row->host_display_name;
                }

                $report = $this->createReportRow($report);
                if ($slaWithBreakdown) {
                    // We create these dimensions only when using report breakdown
                    $dimensions = $report->getDimensions();
                    $dimensions[] = $start->format($format);
                    $report->setDimensions($dimensions);
                }

                $rows[] = $report;

                continue;
            }

            $timelines[$key] = $timeline;
        }

        $rd->setRows($rows);

        return $rd;
    }

    /**
     * Yield start and end times that recur at the specified interval over the given time range
     *
     * @param DateTime $start
     * @param DateTime $end
     * @param DateInterval $interval
     * @param string|null  $boundary English text datetime description for calculating bounds to get
     *                               calendar days, weeks or months instead of relative times according to interval
     *
     * @return Generator
     */
    protected function yieldTimerange(DateTime $start, DateTime $end, DateInterval $interval, $boundary = null)
    {
        $start = clone $start;
        $end = clone $end;
        $oneSecond = new DateInterval('PT1S');

        if ($boundary !== null) {
            $intermediate = (clone $start)->modify($boundary);
            if ($intermediate < $end) {
                yield [clone $start, $intermediate->sub($oneSecond)];

                $start->modify($boundary);
            }
        }

        $period = new DatePeriod($start, $interval, $end, DatePeriod::EXCLUDE_START_DATE);

        foreach ($period as $date) {
            /** @var DateTime $date */
            yield [$start, (clone $date)->sub($oneSecond)];

            $start = $date;
        }

        yield [$start, $end];
    }

    /**
     * Get the initial hard state of the given host/service object
     *
     * @param DateTime $start The start time of the generated sla
     * @param string $hostId Host binary/hex id to fetch the initial hard state for
     *
     * @return array
     */
    protected function fetchInitialHardState(DateTime $start, string $hostId, string $serviceId = null): array
    {
        $serviceFilter = $serviceId === null
            ? Filter::unlike('service_id', '*')
            : Filter::equal('service_id', $serviceId);

        // Use the latest event at or before the beginning of the SLA interval as the initial state.
        $hardState = SlaHistoryState::on($this->getDb())
            ->columns(['hard_state'])
            ->filter(
                Filter::all(
                    Filter::equal('host_id', $hostId),
                    $serviceFilter,
                    Filter::lessThanOrEqual('event_time', $start)
                )
            )
            ->resetOrderBy()
            ->orderBy('event_time', 'DESC')
            ->limit(1);

        $isBefore = true;
        $hardState = $hardState->first();

        // If this doesn't exist, use the previous state from the first event after the beginning of the SLA interval.
        if (! $hardState) {
            $isBefore = false;
            $hardState = SlaHistoryState::on($this->getDb())
                ->columns(['hard_state' => 'previous_hard_state'])
                ->filter(
                    Filter::all(
                        Filter::equal('host_id', $hostId),
                        $serviceFilter,
                        Filter::greaterThan('event_time', $start)
                    )
                );

            $hardState = $hardState->first();

            // If this also doesn't exist, use the current host/service state.
            if (! $hardState) {
                if ($serviceId !== null) {
                    $hardState = ServiceState::on($this->getDb())
                        ->filter(Filter::equal('service_id', $serviceId));
                } else {
                    $hardState = HostState::on($this->getDb());
                }

                $hardState
                    ->columns(['hard_state'])
                    ->filter(Filter::equal('host_id', $hostId));

                $hardState = $hardState->first();
            }
        }

        // Use OK/UP as initial hard state, when neither of the above queries could determine a correct state
        return $hardState === null ? [0, $isBefore] : [(int) $hardState->hard_state, $isBefore];
    }
}
