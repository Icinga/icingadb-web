<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Command\Object;

/**
 * Schedule a check
 */
class ScheduleCheckCommand extends ObjectsCommand
{
    /**
     * Time when the next check of a host or service is to be scheduled
     *
     * If active checks are disabled on a host- or service-specific or program-wide basis or the host or service is
     * already scheduled to be checked at an earlier time, etc. The check may not actually be scheduled at the time
     * specified. This behaviour can be overridden by setting `ScheduledCheck::$forced' to true.
     *
     * @var int Unix timestamp
     */
    protected $checkTime;

    /**
     * Whether the check is forced
     *
     * Forced checks are performed regardless of what time it is (e.g. time period restrictions are ignored) and whether
     * or not active checks are enabled on a host- or service-specific or program-wide basis.
     *
     * @var bool
     */
    protected $forced = false;

    /**
     * Set the time when the next check of a host or service is to be scheduled
     *
     * @param   int $checkTime Unix timestamp
     *
     * @return  $this
     */
    public function setCheckTime(int $checkTime): self
    {
        $this->checkTime = $checkTime;

        return $this;
    }

    /**
     * Get the time when the next check of a host or service is to be scheduled
     *
     * @return int Unix timestamp
     */
    public function getCheckTime(): int
    {
        if ($this->checkTime === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->checkTime;
    }

    /**
     * Set whether the check is forced
     *
     * @param   bool $forced
     *
     * @return  $this
     */
    public function setForced(bool $forced = true): self
    {
        $this->forced = $forced;

        return $this;
    }

    /**
     * Get whether the check is forced
     *
     * @return bool
     */
    public function getForced(): bool
    {
        return $this->forced;
    }
}
