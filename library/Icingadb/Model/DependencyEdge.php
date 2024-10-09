<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Orm\Relations;

/**
 * Dependency edge model.
 *
 * @property string $to_node_id
 * @property string $from_node_id
 * @property ?string $dependency_id
 *
 * @property DependencyNode|Query $child
 * @property DependencyNode|Query $parent
 * @property (?Dependency)|Query $dependency
 */
class DependencyEdge extends Model
{
    public function getTableName(): string
    {
        return 'dependency_edge';
    }

    public function getKeyName(): array
    {
        return ['to_node_id', 'from_node_id'];
    }

    public function getColumns(): array
    {
        return [
            'to_node_id',
            'from_node_id',
            'dependency_id'
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Binary([
            'to_node_id',
            'from_node_id',
            'dependency_id'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('child', DependencyNode::class)
            ->setCandidateKey('from_node_id');
        $relations->belongsTo('parent', DependencyNode::class)
            ->setCandidateKey('to_node_id');
        $relations->belongsTo('dependency', Dependency::class)
            ->setJoinType('LEFT');

        // "from" and "to" are only necessary for sub-query filters.
        $relations->belongsTo('from', DependencyNode::class)
            ->setCandidateKey('from_node_id')
            ->setJoinType('LEFT');

        $relations->belongsTo('to', DependencyNode::class)
            ->setCandidateKey('to_node_id')
            ->setJoinType('LEFT');
    }
}
