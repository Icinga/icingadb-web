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

    public function getMetaData()
    {
        return [
            'environment_id'                => t('Host State Environment Id'),
            'state_type'                    => t('Host State Type'),
            'soft_state'                    => t('Host Soft State'),
            'hard_state'                    => t('Host Hard State'),
            'previous_hard_state'           => t('Host Previous Hard State'),
            'attempt'                       => t('Host State Attempt No.'),
            'severity'                      => t('Host State Severity'),
            'output'                        => t('Host State Output'),
            'long_output'                   => t('Host State Long Output'),
            'performance_data'              => t('Host State Performance Data'),
            'check_commandline'             => t('Host State Check Commandline'),
            'is_problem'                    => t('Host State Is Problem'),
            'is_handled'                    => t('Host State Is Handled'),
            'is_reachable'                  => t('Host State Is Reachable'),
            'is_flapping'                   => t('Host State Is Flapping'),
            'is_overdue'                    => t('Host State Is Overdue'),
            'is_acknowledged'               => t('Host State Is Acknowledged'),
            'acknowledgement_comment_id'    => t('Host State Acknowledgement Comment Id'),
            'in_downtime'                   => t('Host State In Downtime'),
            'execution_time'                => t('Host State Execution Time'),
            'latency'                       => t('Host State Latency'),
            'timeout'                       => t('Host State Timeout'),
            'check_source'                  => t('Host State Check Source'),
            'last_update'                   => t('Host State Last Update'),
            'last_state_change'             => t('Host State Last State Change'),
            'next_check'                    => t('Host State Next Check'),
            'next_update'                   => t('Host State Next Update')
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class);
    }

    /**
     * Get the host state as the textual representation
     *
     * @return string
     */
    public function getStateText()
    {
        return HostStates::text($this->properties['soft_state']);
    }

    /**
     * Get the host state as the translated textual representation
     *
     * @return string
     */
    public function getStateTextTranslated()
    {
        return HostStates::text($this->properties['soft_state']);
    }
}
