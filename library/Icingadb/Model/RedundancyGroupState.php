<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Orm\Relations;

/**
 * Redundancy group state model.
 *
 * @property string $id
 * @property string $redundancy_group_id
 * @property bool $failed
 *
 * @property RedundancyGroup|Query $redundancy_group
 */
class RedundancyGroupState extends Model
{
    public function getTableName(): string
    {
        return 'redundancy_group_state';
    }

    public function getKeyName(): string
    {
        return 'id';
    }

    public function getColumns(): array
    {
        return [
            'redundancy_group_id',
            'failed'
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Binary([
            'id',
            'redundancy_group_id'
        ]));
        $behaviors->add(new BoolCast([
            'failed'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('redundancy_group', RedundancyGroup::class);
    }
}