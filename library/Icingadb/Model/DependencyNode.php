<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Orm\Relations;

/**
 * Dependency node model.
 *
 * @property string $id
 * @property ?string $host_id
 * @property ?string $service_id
 * @property ?string $redundancy_group_id
 *
 * @property (?Host)|Query $host
 * @property (?Service)|Query $service
 * @property (?RedundancyGroup)|Query $redundancy_group
 * @property (?DependencyEdge)|Query $from
 * @property (?DependencyEdge)|Query $to
 * @property (?DependencyNode)|Query $child
 * @property (?DependencyNode)|Query $parent
 */
class DependencyNode extends Model
{
    public function getTableName(): string
    {
        return 'dependency_node';
    }

    public function getKeyName(): string
    {
        return 'id';
    }

    public function getColumns(): array
    {
        return [
            'id',
            'host_id',
            'service_id',
            'redundancy_group_id'
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Binary([
            'id',
            'host_id',
            'service_id',
            'redundancy_group_id'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('host', Host::class)
            ->setJoinType('LEFT');
        $relations->belongsTo('service', Service::class)
            ->setJoinType('LEFT');
        $relations->belongsTo('redundancy_group', RedundancyGroup::class)
            ->setJoinType('LEFT');

        $relations->hasMany('from', DependencyEdge::class)
            ->setForeignKey('from_node_id')
            ->setJoinType('LEFT');
        $relations->hasMany('to', DependencyEdge::class)
            ->setForeignKey('to_node_id')
            ->setJoinType('LEFT');

        $relations->belongsToMany('child', self::class)
            ->through(DependencyEdge::class)
            ->setForeignKey('to_node_id')
            ->setTargetForeignKey('from_node_id')
            ->setJoinType('LEFT');
        $relations->belongsToMany('parent', self::class)
            ->through(DependencyEdge::class)
            ->setForeignKey('from_node_id')
            ->setTargetForeignKey('to_node_id')
            ->setJoinType('LEFT');
    }
}
