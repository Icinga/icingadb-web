<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Common\Backend;
use Icinga\Module\Icingadb\Common\ServiceStates;
use ipl\Orm\Relations;

/**
 * Service state model.
 *
 * @property string $service_id
 */
class ServiceState extends State
{
    public function getTableName()
    {
        return 'service_state';
    }

    public function getKeyName()
    {
        return 'service_id';
    }

    public function getColumnDefinitions()
    {
        $columns = array_merge_recursive(parent::getColumnDefinitions(), [
            'state_type'                    => ['label' => t('Service State Type')],
            'soft_state'                    => ['label' => t('Service Soft State')],
            'hard_state'                    => ['label' => t('Service Hard State')],
            'previous_soft_state'           => ['label' => t('Service Previous Soft State')],
            'previous_hard_state'           => ['label' => t('Service Previous Hard State')],
            'check_attempt'                 => ['label' => t('Service Check Attempt No.')],
            'severity'                      => ['label' => t('Service State Severity')],
            'output'                        => ['label' => t('Service Output')],
            'long_output'                   => ['label' => t('Service Long Output')],
            'performance_data'              => ['label' => t('Service Performance Data')],
            'normalized_performance_data'   => ['label' => t('Service Normalized Performance Data')],
            'check_commandline'             => ['label' => t('Service Check Commandline')],
            'is_problem'                    => ['label' => t('Service Has Problem')],
            'is_handled'                    => ['label' => t('Service Is Handled')],
            'is_reachable'                  => ['label' => t('Service Is Reachable')],
            'is_flapping'                   => ['label' => t('Service Is Flapping')],
            'is_overdue'                    => ['label' => t('Service Check Is Overdue')],
            'is_acknowledged'               => ['label' => t('Service Is Acknowledged')],
            'in_downtime'                   => ['label' => t('Service In Downtime')],
            'execution_time'                => ['label' => t('Service Check Execution Time')],
            'latency'                       => ['label' => t('Service Check Latency')],
            'check_timeout'                 => ['label' => t('Service Check Timeout')],
            'check_source'                  => ['label' => t('Service Check Source')],
            'scheduling_source'             => ['label' => t('Service Scheduling Source')],
            'last_update'                   => ['label' => t('Service Last Update')],
            'last_state_change'             => ['label' => t('Service Last State Change')],
            'next_check'                    => ['label' => t('Service Next Check')],
            'next_update'                   => ['label' => t('Service Next Update')]
        ]);

        if (Backend::supportsDependencies()) {
            $columns['affects_children'] = ['label' => t('Service Affects Children'), 'nullable' => false];
        }

        return $columns;
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('service', Service::class);
        $relations->hasOne('last_comment', LastServiceComment::class)
            ->setCandidateKey('last_comment_id')
            ->setForeignKey('id')
            ->setJoinType('LEFT');
    }

    public function getStateText(): string
    {
        return ServiceStates::text($this->soft_state);
    }

    public function getStateTextTranslated(): string
    {
        return ServiceStates::text($this->soft_state);
    }
}
