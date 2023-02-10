<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook\Reporting\Common;

use Countable;
use DateTimeInterface;

/**
 * Represents a single sla timeline for a given timeframe
 */
class SlaTimeline implements Countable
{
    /** @var int Sla history event type state change */
    public const STATE_CHANGE = 0;

    /** @var int Sla history event type downtime start */
    public const DOWNTIME_START = 1;

    /** @var int Sla history event type downtime end */
    public const DOWNTIME_END = 2;

    /** @var int End of this timeline events */
    public const END_RESULT = 3;

    /** @var DateTimeInterface Start time of the generated report timeframe */
    protected $start;

    /** @var DateTimeInterface End time of the generated report timeframe */
    protected $end;

    /** @var array Sla history events of this timeline */
    protected $events = [];

    /** @var int Sum of the problem time of this timeline */
    protected $problemTime = 0;

    /** @var int Total time of this timeline */
    protected $totalTime = 0;

    /** @var int The initial hard state of this timeline */
    protected $initialHardState = 0;

    /** @var string The object type for which this timeline is used for */
    protected $objectType;

    public function __construct(DateTimeInterface $start, DateTimeInterface $end, string $objectType)
    {
        $this->start = clone $start;
        $this->end = clone $end;
        $this->objectType = $objectType;
    }

    /**
     * Set the initial hard state of this timeline
     *
     * @param int $state
     *
     * @return $this
     */
    public function setInitialHardState(int $state): self
    {
        $this->initialHardState = $state;

        return $this;
    }

    /**
     * Get the calculated SLA result of this timeline
     *
     * @return float
     */
    public function getResult(): float
    {
        $problemTime = 0;
        $activeDowntimes = 0;
        $lastEventTime = (int) $this->start->format('Uv');
        $totalTime = (int) $this->end->format('Uv') - $lastEventTime;

        $lastHardState = $this->initialHardState;
        foreach ($this->events as $event) {
            if ($event->previousHardState === 99) {
                $totalTime -= $event->time - $lastEventTime;
            } elseif (
                (
                    (
                        $this->objectType === 'host'
                        && $lastHardState > 0
                    )
                    || (
                        $this->objectType === 'service'
                        && $lastHardState > 1
                    )
                )
                && $lastHardState !== 99
                && $activeDowntimes === 0
            ) {
                $problemTime += $event->time - $lastEventTime;
            }

            $lastEventTime = $event->time;
            if ($event->type === static::STATE_CHANGE) {
                $lastHardState = $event->hardState;
            } elseif ($event->type === static::DOWNTIME_START) {
                ++$activeDowntimes;
            } elseif ($event->type === static::DOWNTIME_END) {
                --$activeDowntimes;
            }
        }

        $this->problemTime = $problemTime;
        $this->totalTime = $totalTime;

        return 100 * ($totalTime - $problemTime) / $totalTime;
    }

    /**
     * Add history event to this timeline
     *
     * @param object $event
     *
     * @return $this
     */
    public function addEvent(object $event): self
    {
        $this->events[] = $event;

        return $this;
    }

    /**
     * Get the problem time of this timeline
     *
     * @return int
     */
    public function getProblemTime(): int
    {
        return $this->problemTime;
    }

    /**
     * Get the total time of this timeline
     *
     * @return int
     */
    public function getTotalTime(): int
    {
        return $this->totalTime;
    }

    public function count(): int
    {
        return count($this->events);
    }

    public function __toString()
    {
        $timeline = '';
        foreach ($this->events as $event) {
            $timeline .= 'time: ' . $event->time . ' | event: ' . $event->type;
            $timeline .= ' | hard_state: ' . $event->hardState . '| previous_hard_state: ' . $event->previousHardState;

            $timeline .= PHP_EOL;
        }

        return $timeline;
    }
}
