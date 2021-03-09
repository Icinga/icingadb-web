<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Object;

/**
 * Schedule and propagate host downtime
 */
class PropagateHostDowntimeCommand extends ScheduleHostDowntimeCommand
{
    /**
     * Whether the downtime for child hosts are all set to be triggered by this' host downtime
     *
     * @var bool
     */
    protected $triggered = false;

    /**
     * Set whether the downtime for child hosts are all set to be triggered by this' host downtime
     *
     * @param   bool $triggered
     *
     * @return  $this
     */
    public function setTriggered($triggered = true)
    {
        $this->triggered = (bool) $triggered;

        return $this;
    }

    /**
     * Get whether the downtime for child hosts are all set to be triggered by this' host downtime
     *
     * @return bool
     */
    public function getTriggered()
    {
        return $this->triggered;
    }
}
