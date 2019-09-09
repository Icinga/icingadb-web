<?php

namespace Icinga\Module\Eagle\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * Host model.
 */
class Host extends Model
{
    public function getTableName()
    {
        return 'host';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'env_id',
            'name_checksum',
            'properties_checksum',
            'customvars_checksum',
            'groups_checksum',
            'name',
            'name_ci',
            'display_name',
            'address',
            'address',
            'address_bin',
            'address',
            'checkcommand',
            'checkcommand_id',
            'max_check_attempts',
            'check_period',
            'check_period_id',
            'check_timeout',
            'check_interval',
            'check_retry_interval',
            'active_checks_enabled',
            'passive_checks_enabled',
            'event_handler_enabled',
            'notifications_enabled',
            'flapping_enabled',
            'flapping_threshold_low',
            'flapping_threshold_high',
            'perfdata_enabled',
            'eventcommand',
            'eventcommand_id',
            'is_volatile',
            'action_url_id',
            'notes_url_id',
            'notes',
            'icon_image_id',
            'icon_image_alt',
            'zone',
            'zone_id',
            'command_endpoint',
            'command_endpoint_id'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->hasOne('state', HostState::class);
    }
}
