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
 * Dependency state model.
 *
 * @property string $id
 * @property string $dependency_id
 * @property bool $failed
 *
 * @property Dependency|Query $dependency
 */
class DependencyState extends Model
{
    public function getTableName(): string
    {
        return 'dependency_state';
    }

    public function getKeyName(): string
    {
        return 'id';
    }

    public function getColumns(): array
    {
        return [
            'dependency_id',
            'failed'
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Binary([
            'id',
            'dependency_id'
        ]));
        $behaviors->add(new BoolCast([
            'failed'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('dependency', Dependency::class);
    }
}
