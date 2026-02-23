<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
