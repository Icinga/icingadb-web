<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Common\ServiceStates;
use ipl\Orm\Relations;

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
        return [
            'environment_id'                => t('Environment Id'),
            'state_type'                    => t('Service State Type'),
            'soft_state'                    => t('Service Soft State'),
            'hard_state'                    => t('Service Hard State'),
            'previous_soft_state'           => t('Service Previous Soft State'),
            'previous_hard_state'           => t('Service Previous Hard State'),
            'check_attempt'                 => t('Service Check Attempt No.'),
            'severity'                      => t('Service State Severity'),
            'output'                        => t('Service Output'),
            'long_output'                   => t('Service Long Output'),
            'performance_data'              => t('Service Performance Data'),
            'normalized_performance_data'   => t('Service Normalized Performance Data'),
            'check_commandline'             => t('Service Check Commandline'),
            'is_problem'                    => t('Service Has Problem'),
            'is_handled'                    => t('Service Is Handled'),
            'is_reachable'                  => t('Service Is Reachable'),
            'is_flapping'                   => t('Service Is Flapping'),
            'is_overdue'                    => t('Service Check Is Overdue'),
            'is_acknowledged'               => t('Service Is Acknowledged'),
            'acknowledgement_comment_id'    => t('Acknowledgement Comment Id'),
            'in_downtime'                   => t('Service In Downtime'),
            'execution_time'                => t('Service Check Execution Time'),
            'latency'                       => t('Service Check Latency'),
            'check_timeout'                 => t('Service Check Timeout'),
            'check_source'                  => t('Service Check Source'),
            'last_update'                   => t('Service Last Update'),
            'last_state_change'             => t('Service Last State Change'),
            'next_check'                    => t('Service Next Check'),
            'next_update'                   => t('Service Next Update')
        ];
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
