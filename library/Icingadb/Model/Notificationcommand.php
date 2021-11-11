<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Notificationcommand extends Model
{
    public function getTableName()
    {
        return 'notificationcommand';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'zone_id',
            'environment_id',
            'name_checksum',
            'properties_checksum',
            'name',
            'name_ci',
            'command',
            'timeout'
        ];
    }

    public function getMetaData()
    {
        return [
            'zone_id'               => t('Notificationcommand Zone Id'),
            'environment_id'        => t('Notificationcommand Environment Id'),
            'name_checksum'         => t('Notificationcommand Name Checksum'),
            'properties_checksum'   => t('Notificationcommand Properties Checksum'),
            'name'                  => t('Notificationcommand Name'),
            'name_ci'               => t('Notificationcommand Name (CI)'),
            'command'               => t('Notificationcommand'),
            'timeout'               => t('Notificationcommand Timeout')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new ReRoute([
            'host'          => 'notification.host',
            'hostgroup'     => 'notification.host.hostgroup',
            'service'       => 'notification.service',
            'servicegroup'  => 'notification.service.servicegroup'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('zone', Zone::class);

        $relations->belongsToMany('customvar', Customvar::class)
            ->through(NotificationcommandCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(NotificationcommandCustomvar::class);
        $relations->belongsToMany('vars', CustomvarFlat::class)
            ->through(NotificationcommandCustomvar::class);

        $relations->hasMany('notification', Notification::class);
        $relations->hasMany('argument', NotificationcommandArgument::class);
        $relations->hasMany('envvar', NotificationcommandEnvvar::class);
    }
}
