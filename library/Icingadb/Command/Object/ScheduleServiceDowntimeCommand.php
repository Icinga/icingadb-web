<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Object;

/**
 * Schedule a service downtime
 *
 * @deprecated Use {@see ScheduleDowntimeCommand} instead
 */
class ScheduleServiceDowntimeCommand extends ScheduleDowntimeCommand
{
    public function getName(): string
    {
        return 'ScheduleDowntime';
    }
}
