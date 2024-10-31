<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Common\Backend;
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
        $columns = array_merge_recursive(parent::getColumnDefinitions(), [
            'state_type'                    => ['label' => t('Host State Type')],
            'soft_state'                    => ['label' => t('Host Soft State')],
            'hard_state'                    => ['label' => t('Host Hard State')],
            'previous_soft_state'           => ['label' => t('Host Previous Soft State')],
            'previous_hard_state'           => ['label' => t('Host Previous Hard State')],
            'check_attempt'                 => ['label' => t('Host Check Attempt No.')],
            'severity'                      => ['label' => t('Host State Severity')],
            'output'                        => ['label' => t('Host Output')],
            'long_output'                   => ['label' => t('Host Long Output')],
            'performance_data'              => ['label' => t('Host Performance Data')],
            'normalized_performance_data'   => ['label' => t('Host Normalized Performance Data')],
            'check_commandline'             => ['label' => t('Host Check Commandline')],
            'is_problem'                    => ['label' => t('Host Has Problem')],
            'is_handled'                    => ['label' => t('Host Is Handled')],
            'is_reachable'                  => ['label' => t('Host Is Reachable')],
            'is_flapping'                   => ['label' => t('Host Is Flapping')],
            'is_overdue'                    => ['label' => t('Host Check Is Overdue')],
            'is_acknowledged'               => ['label' => t('Host Is Acknowledged')],
            'in_downtime'                   => ['label' => t('Host In Downtime')],
            'execution_time'                => ['label' => t('Host Check Execution Time')],
            'latency'                       => ['label' => t('Host Check Latency')],
            'check_timeout'                 => ['label' => t('Host Check Timeout')],
            'check_source'                  => ['label' => t('Host Check Source')],
            'scheduling_source'             => ['label' => t('Host Scheduling Source')],
            'last_update'                   => ['label' => t('Host Last Update')],
            'last_state_change'             => ['label' => t('Host Last State Change')],
            'next_check'                    => ['label' => t('Host Next Check')],
            'next_update'                   => ['label' => t('Host Next Update')]
        ]);

        if (Backend::supportsDependencies()) {
            $columns['affects_children'] = ['label' => t('Host Affects Children'), 'nullable' => false];
        }

        return $columns;
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class);
        $relations->hasOne('last_comment', LastHostComment::class)
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
