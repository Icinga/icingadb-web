<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use DateTime;
use Icinga\Module\Icingadb\Model\Behavior\BoolCast;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

/**
 * @property string $id
 * @property string $environment_id
 * @property ?string $endpoint_id
 * @property DateTime $heartbeat
 * @property bool $responsible
 * @property bool $icinga2_active_host_checks_enabled
 * @property bool $icinga2_active_service_checks_enabled
 * @property bool $icinga2_event_handlers_enabled
 * @property bool $icinga2_flap_detection_enabled
 * @property bool $icinga2_notifications_enabled
 * @property bool $icinga2_performance_data_enabled
 * @property DateTime $icinga2_start_time
 * @property string $icinga2_version
 */
class Instance extends Model
{
    public function getTableName()
    {
        return 'icingadb_instance';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'environment_id',
            'endpoint_id',
            'heartbeat',
            'responsible',
            'icinga2_active_host_checks_enabled',
            'icinga2_active_service_checks_enabled',
            'icinga2_event_handlers_enabled',
            'icinga2_flap_detection_enabled',
            'icinga2_notifications_enabled',
            'icinga2_performance_data_enabled',
            'icinga2_start_time',
            'icinga2_version'
        ];
    }

    public function getDefaultSort()
    {
        return ['responsible desc', 'heartbeat desc'];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new MillisecondTimestamp([
            'heartbeat',
            'icinga2_start_time'
        ]));

        $behaviors->add(new BoolCast([
            'responsible',
            'icinga2_active_host_checks_enabled',
            'icinga2_active_service_checks_enabled',
            'icinga2_event_handlers_enabled',
            'icinga2_flap_detection_enabled',
            'icinga2_notifications_enabled',
            'icinga2_performance_data_enabled'
        ]));

        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'endpoint_id',
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('endpoint', Endpoint::class)->setJoinType('LEFT');
    }
}
