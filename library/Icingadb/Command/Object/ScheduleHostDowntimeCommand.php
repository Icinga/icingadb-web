<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Object;

/**
 * Schedule a host downtime
 */
class ScheduleHostDowntimeCommand extends ScheduleServiceDowntimeCommand
{
    /**
     * Whether to schedule a downtime for all services associated with a particular host
     *
     * @var bool
     */
    protected $forAllServices = false;

    /**
     * Set whether to schedule a downtime for all services associated with a particular host
     *
     * @param   bool $forAllServices
     *
     * @return  $this
     */
    public function setForAllServices($forAllServices = true)
    {
        $this->forAllServices = (bool) $forAllServices;

        return $this;
    }

    /**
     * Get whether to schedule a downtime for all services associated with a particular host
     *
     * @return bool
     */
    public function getForAllServices()
    {
        return $this->forAllServices;
    }
}
