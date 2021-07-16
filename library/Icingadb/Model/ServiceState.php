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

    public function getMetaData()
    {
        return [
            'environment_id'                => t('Service State Environment Id'),
            'state_type'                    => t('Service State Type'),
            'soft_state'                    => t('Service Soft State'),
            'hard_state'                    => t('Service Hard State'),
            'previous_hard_state'           => t('Service Previous Hard State'),
            'attempt'                       => t('Service State Attempt No.'),
            'severity'                      => t('Service State Severity'),
            'output'                        => t('Service State Output'),
            'long_output'                   => t('Service State Long Output'),
            'performance_data'              => t('Service State Performance Data'),
            'normalized_performance_data'   => t('Service State Normalized Performance Data'),
            'check_commandline'             => t('Service State Check Commandline'),
            'is_problem'                    => t('Service State Is Problem'),
            'is_handled'                    => t('Service State Is Handled'),
            'is_reachable'                  => t('Service State Is Reachable'),
            'is_flapping'                   => t('Service State Is Flapping'),
            'is_overdue'                    => t('Service State Is Overdue'),
            'is_acknowledged'               => t('Service State Is Acknowledged'),
            'acknowledgement_comment_id'    => t('Service State Acknowledgement Comment Id'),
            'in_downtime'                   => t('Service State In Downtime'),
            'execution_time'                => t('Service State Execution Time'),
            'latency'                       => t('Service State Latency'),
            'timeout'                       => t('Service State Timeout'),
            'check_source'                  => t('Service State Check Source'),
            'last_update'                   => t('Service State Last Update'),
            'last_state_change'             => t('Service State Last State Change'),
            'next_check'                    => t('Service State Next Check'),
            'next_update'                   => t('Service State Next Update')
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('service', Service::class);
    }

    /**
     * Get the host state as the textual representation
     *
     * @return string
     */
    public function getStateText()
    {
        return ServiceStates::text($this->properties['soft_state']);
    }

    /**
     * Get the host state as the translated textual representation
     *
     * @return string
     */
    public function getStateTextTranslated()
    {
        return ServiceStates::text($this->properties['soft_state']);
    }
}
