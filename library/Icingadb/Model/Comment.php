<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Model;

use DateTime;
use ipl\Orm\Behavior\BoolCast;
use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use Icinga\Module\Icingadb\Common\Model;
use ipl\Orm\Relations;

/**
 * @property string $id
 * @property string $environment_id
 * @property string $object_type
 * @property string $host_id
 * @property ?string $service_id
 * @property string $name_checksum
 * @property string $properties_checksum
 * @property string $name
 * @property string $author
 * @property string $text
 * @property string $entry_type
 * @property DateTime $entry_time
 * @property bool $is_persistent
 * @property bool $is_sticky
 * @property ?DateTime $expire_time
 * @property ?string $zone_id
 */
class Comment extends Model
{
    public function getTableName()
    {
        return 'comment';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'environment_id',
            'object_type',
            'host_id',
            'service_id',
            'name_checksum',
            'properties_checksum',
            'name',
            'author',
            'text',
            'entry_type',
            'entry_time',
            'is_persistent',
            'is_sticky',
            'expire_time',
            'zone_id'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'environment_id'        => t('Environment Id'),
            'object_type'           => t('Object Type'),
            'host_id'               => t('Host Id'),
            'service_id'            => t('Service Id'),
            'name_checksum'         => t('Comment Name Checksum'),
            'properties_checksum'   => t('Comment Properties Checksum'),
            'name'                  => t('Comment Name'),
            'author'                => t('Comment Author'),
            'text'                  => t('Comment Text'),
            'entry_type'            => t('Comment Type'),
            'entry_time'            => t('Comment Entry Time'),
            'is_persistent'         => t('Comment Is Persistent'),
            'is_sticky'             => t('Comment Is Sticky'),
            'expire_time'           => t('Comment Expire Time'),
            'zone_id'               => t('Zone Id')
        ];
    }

    public function getSearchColumns()
    {
        return ['text'];
    }

    public function getDefaultSort()
    {
        return 'comment.entry_time desc';
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new BoolCast([
            'is_persistent',
            'is_sticky'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'entry_time',
            'expire_time'
        ]));

        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'host_id',
            'service_id',
            'name_checksum',
            'properties_checksum',
            'zone_id'
        ]));

        $behaviors->add(new ReRoute([
            'hostgroup'     => 'host.hostgroup',
            'servicegroup'  => 'service.servicegroup'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class)->setJoinType('LEFT');
        $relations->belongsTo('service', Service::class)->setJoinType('LEFT');
        $relations->belongsTo('zone', Zone::class);
    }
}
