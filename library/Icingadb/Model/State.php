<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\BoolCast;
use Icinga\Module\Icingadb\Model\Behavior\Timestamp;
use Icinga\Module\Icingadb\Model\Behavior\VolatileState;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;

/**
 * Base class for the {@link HostState} and {@link ServiceState} models providing common columns.
 */
abstract class State extends Model
{
    public function getColumns()
    {
        return [
            'environment_id',
            'state_type',
            'soft_state',
            'hard_state',
            'previous_hard_state',
            'attempt',
            'severity',
            'output',
            'long_output',
            'performance_data',
            'normalized_performance_data',
            'check_commandline',
            'is_problem',
            'is_handled',
            'is_reachable',
            'is_flapping',
            'is_overdue',
            'is_acknowledged',
            'acknowledgement_comment_id',
            'in_downtime',
            'execution_time',
            'latency',
            'timeout',
            'check_source',
            'scheduling_source',
            'last_update',
            'last_state_change',
            'next_check',
            'next_update'
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new VolatileState());
        $behaviors->add(new BoolCast([
            'is_problem',
            'is_handled',
            'is_reachable',
            'is_flapping',
            'is_overdue',
            'is_acknowledged',
            'in_downtime'
        ]));
        $behaviors->add(new Timestamp([
            'execution_time',
            'latency',
            'last_update',
            'last_state_change',
            'next_check',
            'next_update'
        ]));
    }
}
