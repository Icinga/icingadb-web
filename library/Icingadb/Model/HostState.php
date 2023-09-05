<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Common\HostStates;
use ipl\Orm\Relations;

/**
 * Host state model.
 */
class HostState extends State
{
    public function getTableName()
    {
        return 'host_state';
    }

    public function getKeyName()
    {
        return 'host_id';
    }

    public function getColumnDefinitions()
    {
        return [
            'environment_id'                => t('Environment Id'),
            'state_type'                    => t('Host State Type'),
            'soft_state'                    => t('Host Soft State'),
            'hard_state'                    => t('Host Hard State'),
            'previous_soft_state'           => t('Host Previous Soft State'),
            'previous_hard_state'           => t('Host Previous Hard State'),
            'check_attempt'                 => t('Host Check Attempt No.'),
            'severity'                      => t('Host State Severity'),
            'output'                        => t('Host Output'),
            'long_output'                   => t('Host Long Output'),
            'performance_data'              => t('Host Performance Data'),
            'normalized_performance_data'   => t('Host Normalized Performance Data'),
            'check_commandline'             => t('Host Check Commandline'),
            'is_problem'                    => t('Host Has Problem'),
            'is_handled'                    => t('Host Is Handled'),
            'is_reachable'                  => t('Host Is Reachable'),
            'is_flapping'                   => t('Host Is Flapping'),
            'is_overdue'                    => t('Host Check Is Overdue'),
            'is_acknowledged'               => t('Host Is Acknowledged'),
            'acknowledgement_comment_id'    => t('Acknowledgement Comment Id'),
            'in_downtime'                   => t('Host In Downtime'),
            'execution_time'                => t('Host Check Execution Time'),
            'latency'                       => t('Host Check Latency'),
            'check_timeout'                 => t('Host Check Timeout'),
            'check_source'                  => t('Host Check Source'),
            'last_update'                   => t('Host Last Update'),
            'last_state_change'             => t('Host Last State Change'),
            'next_check'                    => t('Host Next Check'),
            'next_update'                   => t('Host Next Update')
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class);
        $relations->hasOne('last_comment', Comment::class)
            ->setCandidateKey('last_comment_id')
            ->setForeignKey('id')
            ->setJoinType('LEFT');
    }


    public function getStateText(): string
    {
        return HostStates::text($this->soft_state);
    }


    public function getStateTextTranslated(): string
    {
        return HostStates::text($this->soft_state);
    }
}
