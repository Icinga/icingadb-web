<?php

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behaviors;
use Icinga\Module\Icingadb\Common\Model;
use ipl\Orm\Relations;

/**
 * Dependency edge state model.
 *
 * @property string $id
 * @property string $environment_id
 * @property bool $failed
 */
class DependencyEdgeState extends Model
{
    public function getTableName(): string
    {
        return 'dependency_edge_state';
    }

    public function getKeyName(): string
    {
        return 'id';
    }

    public function getColumns(): array
    {
        return [
            'environment_id',
            'failed'
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Binary([
            'id',
            'environment_id'
        ]));
        $behaviors->add(new BoolCast([
            'failed'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->hasMany('edge', DependencyEdge::class);
    }
}
