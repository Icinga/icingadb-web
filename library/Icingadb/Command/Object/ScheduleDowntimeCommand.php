<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Object;

/**
 * Schedule a host or service downtime
 *
 * @phpstan-type CHILD_OPTION self::IGNORE_CHILDREN|self::TRIGGER_CHILDREN|self::SCHEDULE_CHILDREN
 */
class ScheduleDowntimeCommand extends AddCommentCommand
{
    /** @var int Do not do anything with children */
    public const IGNORE_CHILDREN = 0;

    /** @var int Schedule a downtime for all children and trigger them by this downtime */
    public const TRIGGER_CHILDREN = 1;

    /** @var int Schedule a downtime for all children but do not trigger them by this downtime */
    public const SCHEDULE_CHILDREN = 2;

    /**
     * Downtime starts at the exact time specified
     *
     * If `Downtime::$fixed' is set to false, the time between `Downtime::$start' and `Downtime::$end' at which a
     * host or service transitions to a problem state determines the time at which the downtime actually starts.
     * The downtime will then last for `Downtime::$duration' seconds.
     *
     * @var int Unix timestamp
     */
    protected $start;

    /**
     * Downtime ends at the exact time specified
     *
     * If `Downtime::$fixed' is set to false, the time between `Downtime::$start' and `Downtime::$end' at which a
     * host or service transitions to a problem state determines the time at which the downtime actually starts.
     * The downtime will then last for `Downtime::$duration' seconds.
     *
     * @var int Unix timestamp
     */
    protected $end;

    /**
     * Whether it's a fixed or flexible downtime
     *
     * @var bool
     */
    protected $fixed = true;

    /**
     * ID of the downtime which triggers this downtime
     *
     * The start of this downtime is triggered by the start of the other scheduled host or service downtime.
     *
     * @var int|null
     */
    protected $triggerId;

    /**
     * The duration in seconds the downtime must last if it's a flexible downtime
     *
     * If `Downtime::$fixed' is set to false, the downtime will last for the duration in seconds specified, even
     * if the host or service recovers before the downtime expires.
     *
     * @var int|null
     */
    protected $duration;

    /**
     * Whether to schedule a downtime for all services associated with a particular host
     *
     * @var bool
     */
    protected $forAllServices = false;

    /**
     * What to do with children
     *
     * @var CHILD_OPTION
     */
    protected $childOption = self::IGNORE_CHILDREN;

    /**
     * Set the time when the downtime should start
     *
     * @param   int $start Unix timestamp
     *
     * @return  $this
     */
    public function setStart(int $start): self
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get the time when the downtime should start
     *
     * @return int Unix timestamp
     */
    public function getStart(): int
    {
        if ($this->start === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->start;
    }

    /**
     * Set the time when the downtime should end
     *
     * @param   int $end Unix timestamp
     *
     * @return  $this
     */
    public function setEnd(int $end): self
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Get the time when the downtime should end
     *
     * @return int Unix timestamp
     */
    public function getEnd(): int
    {
        if ($this->start === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->end;
    }

    /**
     * Set whether it's a fixed or flexible downtime
     *
     * @param   boolean $fixed
     *
     * @return  $this
     */
    public function setFixed(bool $fixed = true): self
    {
        $this->fixed = $fixed;

        return $this;
    }

    /**
     * Is the downtime fixed?
     *
     * @return boolean
     */
    public function getFixed(): bool
    {
        return $this->fixed;
    }

    /**
     * Set the ID of the downtime which triggers this downtime
     *
     * @param   int $triggerId
     *
     * @return  $this
     */
    public function setTriggerId(int $triggerId): self
    {
        $this->triggerId = $triggerId;

        return $this;
    }

    /**
     * Get the ID of the downtime which triggers this downtime
     *
     * @return int|null
     */
    public function getTriggerId()
    {
        return $this->triggerId;
    }

    /**
     * Set the duration in seconds the downtime must last if it's a flexible downtime
     *
     * @param   int $duration
     *
     * @return  $this
     */
    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get the duration in seconds the downtime must last if it's a flexible downtime
     *
     * @return int|null
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set whether to schedule a downtime for all services associated with a particular host
     *
     * @param   bool $forAllServices
     *
     * @return  $this
     */
    public function setForAllServices(bool $forAllServices = true): self
    {
        $this->forAllServices = $forAllServices;

        return $this;
    }

    /**
     * Get whether to schedule a downtime for all services associated with a particular host
     *
     * @return bool
     */
    public function getForAllServices(): bool
    {
        return $this->forAllServices;
    }

    /**
     * Set what to do with children
     *
     * @param CHILD_OPTION $option
     *
     * @return $this
     */
    public function setChildOption(int $option): self
    {
        $this->childOption = $option;

        return $this;
    }

    /**
     * Get what to do with children
     *
     * @return CHILD_OPTION
     */
    public function getChildOption(): int
    {
        return $this->childOption;
    }
}
