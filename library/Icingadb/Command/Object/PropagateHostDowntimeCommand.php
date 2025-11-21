<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Command\Object;

/**
 * Schedule and propagate host downtime
 *
 * @deprecated Use {@see ScheduleDowntimeCommand} instead
 */
class PropagateHostDowntimeCommand extends ScheduleHostDowntimeCommand
{
    protected $childOption = ScheduleDowntimeCommand::SCHEDULE_CHILDREN;

    /**
     * Set whether the downtime for child hosts are all set to be triggered by this' host downtime
     *
     * @param   bool $triggered
     *
     * @return  $this
     */
    public function setTriggered(bool $triggered = true): self
    {
        return $this->setChildOption(
            $triggered
            ? ScheduleDowntimeCommand::TRIGGER_CHILDREN
            : ScheduleDowntimeCommand::SCHEDULE_CHILDREN
        );
    }

    /**
     * Get whether the downtime for child hosts are all set to be triggered by this' host downtime
     *
     * @return bool
     */
    public function getTriggered(): bool
    {
        return $this->getChildOption() === ScheduleDowntimeCommand::TRIGGER_CHILDREN;
    }
}
