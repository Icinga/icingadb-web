<?php

namespace Icinga\Module\Eagle\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class NotificationUser extends Model
{
    public function getTableName()
    {
        return 'notification_user';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'notification_id',
            'user_id',
            'env_id'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('notification', Notification::class);
        $relations->belongsTo('user', User::class);
    }
}
