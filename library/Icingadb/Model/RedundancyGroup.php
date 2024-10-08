<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Orm\Relations;

/**
 * Redundancy group model.
 *
 * @property string $id
 * @property string $name
 * @property string $display_name
 *
 * @property (?RedundancyGroupState)|Query $state
 * @property Dependency|Query $dependency
 * @property (?DependencyEdge)|Query $from
 * @property (?DependencyEdge)|Query $to
 */
class RedundancyGroup extends Model
{
    public function getTableName(): string
    {
        return 'redundancy_group';
    }

    public function getKeyName(): string
    {
        return 'id';
    }

    public function getColumns(): array
    {
        return [
            'name',
            'display_name'
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Binary([
            'id'
        ]));
        $behaviors->add(new ReRoute([
            'child' => 'to.from',
            'parent' => 'from.to'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->hasOne('state', RedundancyGroupState::class)
            ->setJoinType('LEFT');

        $relations->hasMany('dependency', Dependency::class);

        $relations->belongsToMany('from', DependencyEdge::class)
            ->setTargetCandidateKey('from_node_id')
            ->setTargetForeignKey('id')
            ->through(DependencyNode::class)
            ->setJoinType('LEFT');

        $relations->belongsToMany('to', DependencyEdge::class)
            ->setTargetCandidateKey('to_node_id')
            ->setTargetForeignKey('id')
            ->through(DependencyNode::class)
            ->setJoinType('LEFT');
    }
}
