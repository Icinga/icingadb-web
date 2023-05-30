<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\BoolCast;
use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Downtime extends Model
{
    public function getTableName()
    {
        return 'downtime';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'environment_id',
            'triggered_by_id',
            'parent_id',
            'object_type',
            'host_id',
            'service_id',
            'name_checksum',
            'properties_checksum',
            'name',
            'author',
            'comment',
            'entry_time',
            'scheduled_start_time',
            'scheduled_end_time',
            'scheduled_duration',
            'is_flexible',
            'flexible_duration',
            'is_in_effect',
            'start_time',
            'end_time',
            'duration',
            'scheduled_by',
            'zone_id'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'environment_id'       => t('Environment Id'),
            'triggered_by_id'      => t('Triggered By Downtime Id'),
            'parent_id'            => t('Parent Downtime Id'),
            'object_type'          => t('Object Type'),
            'host_id'              => t('Host Id'),
            'service_id'           => t('Service Id'),
            'name_checksum'        => t('Downtime Name Checksum'),
            'properties_checksum'  => t('Downtime Properties Checksum'),
            'name'                 => t('Downtime Name'),
            'author'               => t('Downtime Author'),
            'comment'              => t('Downtime Comment'),
            'entry_time'           => t('Downtime Entry Time'),
            'scheduled_start_time' => t('Downtime Scheduled Start'),
            'scheduled_end_time'   => t('Downtime Scheduled End'),
            'scheduled_duration'   => t('Downtime Scheduled Duration'),
            'is_flexible'          => t('Downtime Is Flexible'),
            'flexible_duration'    => t('Downtime Flexible Duration'),
            'is_in_effect'         => t('Downtime Is In Effect'),
            'start_time'           => t('Downtime Actual Start'),
            'end_time'             => t('Downtime Actual End'),
            'duration'             => t('Downtime Duration'),
            'scheduled_by'         => t('Scheduled By Downtime'),
            'zone_id'              => t('Zone Id')
        ];
    }

    public function getSearchColumns()
    {
        return ['comment'];
    }

    public function getDefaultSort()
    {
        return ['downtime.is_in_effect desc', 'downtime.start_time desc'];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new BoolCast([
            'is_flexible',
            'is_in_effect'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'entry_time',
            'scheduled_start_time',
            'scheduled_end_time',
            'start_time',
            'end_time'
        ]));

        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'triggered_by_id',
            'parent_id',
            'host_id',
            'service_id',
            'name_checksum',
            'properties_checksum',
            'zone_id'
        ]));

        // As long as the rewriteCondition() expects only Filter\Condition as a first argument
        // We have to add this reroute behavior after the binary because the filter condition might
        // be transformed into a filter chain!
        $behaviors->add(new ReRoute([
            'hostgroup'    => 'host.hostgroup',
            'servicegroup' => 'service.servicegroup'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('triggered_by', self::class)
            ->setCandidateKey('triggered_by_id')
            ->setJoinType('LEFT');
        $relations->belongsTo('parent', self::class)
            ->setCandidateKey('parent_id')
            ->setJoinType('LEFT');
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class)->setJoinType('LEFT');
        $relations->belongsTo('service', Service::class)->setJoinType('LEFT');
        $relations->belongsTo('zone', Zone::class);
    }
}
