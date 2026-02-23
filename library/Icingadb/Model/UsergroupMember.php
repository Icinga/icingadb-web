<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use Icinga\Module\Icingadb\Common\Model;
use ipl\Orm\Relations;

/**
 * @property string $id
 * @property string $usergroup_id
 * @property string $user_id
 * @property string $environment_id
 */
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

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'user_id',
            'usergroup_id',
            'environment_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('usergroup', Usergroup::class);
        $relations->belongsTo('user', User::class);
    }
}
