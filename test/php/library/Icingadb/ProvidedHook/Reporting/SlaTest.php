<?php

namespace Tests\Icinga\Modules\Icingadb\ProvidedHook\Reporting;

use DateTime;
use ipl\Sql\Connection;
use PHPUnit\Framework\TestCase;
use Tests\Icinga\Module\Icingadb\Lib\SlaReportWithCustomDb;

class SlaTest extends TestCase
{
    /** @var SlaReportWithCustomDb */
    protected $report;

    /** @var Connection */
    protected $conn;

    protected $hostId;

    protected $serviceId;

    /** @var DateTime */
    protected $start;

    /** @var DateTime */
    protected $end;

    protected function setUp(): void
    {
        parent::setUp();

        $this->report = new SlaReportWithCustomDb();
        $this->conn = $this->report->getDb();

        $this->start = (new DateTime())->setTimestamp(1000);
        $this->end = (new DateTime())->setTimestamp(2000);

        $this->insertHostAndService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->conn->exec('DROP TABLE IF EXISTS host');
        $this->conn->exec('DROP TABLE IF EXISTS host_state');
        $this->conn->exec('DROP TABLE IF EXISTS service');
        $this->conn->exec('DROP TABLE IF EXISTS service_state');
        $this->conn->exec('DROP TABLE IF EXISTS sla_history_state');
        $this->conn->exec('DROP TABLE IF EXISTS sla_history_downtime');

        $this->report->resetConn();
    }

    public function testEmptyHistoryEvents()
    {
        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'host', $this->hostId);
        $this->assertSame(100.0, $timeline->getResult());

        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'service', $this->serviceId);
        $this->assertSame(100.0, $timeline->getResult());
    }

    public function testMultipleStateChanges()
    {
        $this->insertSlaHistoryEvents([
            'state' => [
                ['event_time' => 1000000, 'hard_state' => 2, 'previous_hard_state' => 99], // -10%
                ['event_time' => 1100000, 'hard_state' => 0, 'previous_hard_state' => 2], // OK
                ['event_time' => 1300000, 'hard_state' => 2, 'previous_hard_state' => 0], // -10%
                ['event_time' => 1400000, 'hard_state' => 0, 'previous_hard_state' => 2], // OK
                ['event_time' => 1600000, 'hard_state' => 2, 'previous_hard_state' => 0], // -10%
                ['event_time' => 1700000, 'hard_state' => 0, 'previous_hard_state' => 2], // OK
                ['event_time' => 1900000, 'hard_state' => 2, 'previous_hard_state' => 0], // -10%
            ]
        ]);

        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'host', $this->hostId);
        $this->assertSame(60.0, $timeline->getResult());

        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'service', $this->serviceId);
        $this->assertSame(60.0, $timeline->getResult());
    }

    public function testOverlappingDowntimesAndProblems()
    {
        $this->insertSlaHistoryEvents([
            'state'    => [
                ['event_time' => 1200000, 'hard_state' => 2, 'previous_hard_state' => 0],
                ['event_time' => 1500000, 'hard_state' => 0, 'previous_hard_state' => 2]
            ],
            'downtime' => [
                ['downtime_id' => $this->makeId(), 'downtime_start' => 1100000, 'downtime_end' => 1300000],
                ['downtime_id' => $this->makeId(), 'downtime_start' => 1400000, 'downtime_end' => 1600000]
            ],
        ]);

        // 1000..1100: OK, no downtime
        // 1100..1200: OK, in downtime
        // 1200..1300: CRITICAL, in downtime
        // 1300..1400: CRITICAL, no downtime (only period counting for SLA, -10%)
        // 1400..1500: CRITICAL, in downtime
        // 1500..1600: OK, in downtime
        // 1600..2000: OK, no downtime
        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'host', $this->hostId);
        $this->assertSame(90.0, $timeline->getResult());

        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'service', $this->serviceId);
        $this->assertSame(90.0, $timeline->getResult());
    }

    public function testCriticalBeforeInterval()
    {
        $this->insertSlaHistoryEvents(
            ['state' => [['event_time' => 0, 'hard_state' => 2, 'previous_hard_state' => 99]]]
        );

        // If there is no event within the SLA interval, the last state from before the interval should be used.
        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'host', $this->hostId);
        $this->assertSame(0.0, $timeline->getResult());

        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'service', $this->serviceId);
        $this->assertSame(0.0, $timeline->getResult());
    }

    public function testCriticalBeforeIntervalWithDowntime()
    {
        $this->insertSlaHistoryEvents([
            'state'    => [['event_time' => 800000, 'hard_state' => 2, 'previous_hard_state' => 99]],
            'downtime' => [['downtime_id' => $this->makeId(), 'downtime_start' => 600000, 'downtime_end' => 1500000]]
        ]);

        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'host', $this->hostId);
        $this->assertSame(50.0, $timeline->getResult());

        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'service', $this->serviceId);
        $this->assertSame(50.0, $timeline->getResult());
    }

    public function testCriticalBeforeIntervalWithOverlappingDowntimes()
    {
        $this->insertSlaHistoryEvents([
            'state'    => [['event_time' => 800000, 'hard_state' => 2, 'previous_hard_state' => 99]],
            'downtime' => [
                ['downtime_id' => $this->makeId(), 'downtime_start' => 600000, 'downtime_end' => 1000000],
                ['downtime_id' => $this->makeId(), 'downtime_start' => 800000, 'downtime_end' => 1200000],
                ['downtime_id' => $this->makeId(), 'downtime_start' => 1000000, 'downtime_end' => 1400000],
                ['downtime_id' => $this->makeId(), 'downtime_start' => 1600000, 'downtime_end' => 2000000],
                // Everything except 1400-1600 is covered by downtimes, -20%
                ['downtime_id' => $this->makeId(), 'downtime_start' => 1800000, 'downtime_end' => 2200000]
            ]
        ]);

        // Test that overlapping downtimes are properly accounted for.
        // The period from 1400 to 1600 represents 20% of the total time, and since there was only
        // one state change 2 (DOWN) before the sla interval, that 20% is a problem time.
        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'host', $this->hostId);
        $this->assertSame(80.0, $timeline->getResult());

        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'service', $this->serviceId);
        $this->assertSame(80.0, $timeline->getResult());
    }

    public function testFallbackToPreviousState()
    {
        $this->insertSlaHistoryEvents(
            ['state' => [['event_time' => 1100000, 'hard_state' => 0, 'previous_hard_state' => 2]]]
        );

        // If there is no state event from before the SLA interval, the previous hard state from the first event
        // after the beginning of the SLA interval should be used as the initial state.
        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'host', $this->hostId);
        $this->assertSame(90.0, $timeline->getResult());

        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'service', $this->serviceId);
        $this->assertSame(90.0, $timeline->getResult());
    }

    public function testFallbackToCurrentState()
    {
        $this->insertObjectCurrentState(2);

        // If there are no state history events, the current state of the checkable should be used.
        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'host', $this->hostId);
        $this->assertSame(0.0, $timeline->getResult());

        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'service', $this->serviceId);
        $this->assertSame(0.0, $timeline->getResult());
    }

    public function testPreferInitialStateFromBeforeOverLaterState()
    {
        $this->insertSlaHistoryEvents([
            'state' => [
                ['event_time' => 800000, 'hard_state' => 2, 'previous_hard_state' => 99],
                ['event_time' => 1600000, 'hard_state' => 0, 'previous_hard_state' => 0],
            ]
        ]);

        // The previous_hard_state should only be used as a fallback
        // when there is no event from before the SLA interval.
        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'host', $this->hostId);
        $this->assertSame(40.0, $timeline->getResult());

        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'service', $this->serviceId);
        $this->assertSame(40.0, $timeline->getResult());
    }

    public function testPreferInitialStateFromBeforeOverCurrentState()
    {
        $this->insertObjectCurrentState(0);
        $this->insertSlaHistoryEvents(
            ['state' => [['event_time' => 800000, 'hard_state' => 2, 'previous_hard_state' => 99]]]
        );

        // The current state should only be used as a fallback when there is no state history event.
        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'host', $this->hostId);
        $this->assertSame(0.0, $timeline->getResult());

        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'service', $this->serviceId);
        $this->assertSame(0.0, $timeline->getResult());
    }

    public function testPreferLaterStateOverCurrentState()
    {
        $this->insertObjectCurrentState(2);
        $this->insertSlaHistoryEvents(
            ['state' => [['event_time' => 1300000, 'hard_state' => 0, 'previous_hard_state' => 2]]]
        );

        // The current state should only be used as a fallback when there is no state history event.
        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'host', $this->hostId);
        $this->assertSame(70.0, $timeline->getResult());

        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'service', $this->serviceId);
        $this->assertSame(70.0, $timeline->getResult());
    }

    public function testInitialPendingStateReducesTotalTime()
    {
        $this->insertObjectCurrentState(0);
        $this->insertSlaHistoryEvents([
            'state' => [
                ['event_time' => 1600000, 'hard_state' => 2, 'previous_hard_state' => 99],
                ['event_time' => 1700000, 'hard_state' => 0, 'previous_hard_state' => 2]
            ]
        ]);

        // 1000..1600: PENDING (600s)
        // 1600..1700: DOWN|CRITICAL (100s)
        // 1700..2000: OK
        // Total: 2000 - 1000 = 1000
        // total -= 600s PENDING time = 400
        // sla = 100 * (total - 100s PROBLEM TIME) / 400 TOTAL = 75%
        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'host', $this->hostId);
        $this->assertSame(75.0, $timeline->getResult());

        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'service', $this->serviceId);
        $this->assertSame(75.0, $timeline->getResult());
    }

    public function testIntermediatePendingStateReducesTotalTime()
    {
        $this->insertObjectCurrentState(0);
        $this->insertSlaHistoryEvents([
            'state' => [
                ['event_time' => 1000000, 'hard_state' => 0, 'previous_hard_state' => 2],
                ['event_time' => 1100000, 'hard_state' => 2, 'previous_hard_state' => 0],
                ['event_time' => 1600000, 'hard_state' => 0, 'previous_hard_state' => 99],
                ['event_time' => 1800000, 'hard_state' => 2, 'previous_hard_state' => 0]
            ]
        ]);

        // 1000..1100: OK|UP
        // 1100..1600: PENDING (500s)
        // 1600..1800: OK|UP
        // 1800..2000: DOWN|CRITICAL (200s PROBLEM TIME)
        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'host', $this->hostId);
        $this->assertSame(60.0, $timeline->getResult());

        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'service', $this->serviceId);
        $this->assertSame(60.0, $timeline->getResult());
    }

    public function testPendingStateAfterIntervalEndReducesTotalTime()
    {
        $this->insertSlaHistoryEvents([
            'state' => [['event_time' => 2500000, 'hard_state' => 0, 'previous_hard_state' => 99]]
        ]);

        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'host', $this->hostId);
        $this->assertNull($timeline->getResult());

        $timeline = $this->report->getSlaTimeline($this->start, $this->end, 'service', $this->serviceId);
        $this->assertNull($timeline->getResult());
    }

    protected function makeId(): string
    {
        return random_bytes(20);
    }

    protected function insertHostAndService()
    {
        $this->hostId = $this->makeId();
        $this->conn->insert('host', [
            'id'           => $this->hostId,
            'display_name' => 'icinga2'
        ]);

        $this->serviceId = $this->makeId();
        $this->conn->insert('service', [
            'id'           => $this->serviceId,
            'host_id'      => $this->hostId,
            'display_name' => 'disk'
        ]);
    }

    protected function insertObjectCurrentState(int $state)
    {
        $this->conn->insert('host_state', [
            'id'         => $this->makeId(),
            'host_id'    => $this->hostId,
            'hard_state' => $state
        ]);
        $this->conn->insert('service_state', [
            'id'         => $this->makeId(),
            'host_id'    => $this->hostId,
            'service_id' => $this->serviceId,
            'hard_state' => $state
        ]);
    }

    protected function insertSlaHistoryEvents(array $histories)
    {
        foreach ($histories as $eventType => $vents) {
            $table = $eventType === 'state' ? 'sla_history_state' : 'sla_history_downtime';
            foreach ($vents as $vent) {
                foreach (['host', 'service'] as $objectType) {
                    $vent['id'] = $this->makeId();
                    $vent['host_id'] = $this->hostId;
                    $vent['object_type'] = $objectType;
                    if ($objectType === 'service') {
                        $vent['service_id'] = $this->serviceId;
                    }

                    $this->conn->insert($table, $vent);
                }
            }
        }
    }
}
