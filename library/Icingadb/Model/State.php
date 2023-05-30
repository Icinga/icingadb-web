<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\BoolCast;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
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
            'previous_soft_state',
            'previous_hard_state',
            'check_attempt',
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
            'last_comment_id',
            'in_downtime',
            'execution_time',
            'latency',
            'check_timeout',
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
        $behaviors->add(new BoolCast([
            'is_problem',
            'is_handled',
            'is_reachable',
            'is_flapping',
            'is_overdue',
            'is_acknowledged',
            'in_downtime'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'last_update',
            'last_state_change',
            'next_check',
            'next_update'
        ]));

        $behaviors->add(new Binary([
            $this->getKeyName(),
            'environment_id',
            'acknowledgement_comment_id',
            'last_comment_id'
        ]));
    }
}
