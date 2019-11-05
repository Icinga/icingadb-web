<?php

namespace Icinga\Module\Eagle\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class UsergroupMember extends Model
{
    public function getTableName()
    {
        return 'usergroup_member';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'user_id',
            'usergroup_id',
            'environment_id'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('usergroup', Usergroup::class);
        $relations->belongsTo('user', User::class);
    }
}
