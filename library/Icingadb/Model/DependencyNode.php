<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Orm\Relations;
use ipl\Sql\Expression;

/**
 * Dependency node model.
 *
 * @property string $id
 * @property ?string $host_id
 * @property ?string $service_id
 * @property ?string $redundancy_group_id
 * @property ?string $name
 * @property ?int $severity
 * @property ?int $state
 * @property ?int $last_state_change
 *
 * @property (?Host)|Query $host
 * @property (?Service)|Query $service
 * @property (?RedundancyGroup)|Query $redundancy_group
 * @property (?DependencyEdge)|Query $from
 * @property (?DependencyEdge)|Query $to
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
            'redundancy_group_id',
            'name' => new Expression(
                'CASE WHEN %s IS NOT NULL THEN %s WHEN %s IS NOT NULL THEN %s ELSE %s END',
                ['service_id', 'service.display_name', 'host_id', 'host.display_name', 'redundancy_group.display_name']
            ),
            'severity' => new Expression(
                'CASE WHEN %s IS NOT NULL THEN %s WHEN %s IS NOT NULL THEN %s ELSE %s END',
                [
                    'service_id',
                    'service.state.severity',
                    'host_id',
                    'host.state.severity',
                    'redundancy_group.state.failed'
                ]
            ),
            'state' => new Expression(
                'CASE WHEN %s IS NOT NULL THEN %s WHEN %s IS NOT NULL THEN %s ELSE %s END',
                [
                    'service_id',
                    'service.state.soft_state',
                    'host_id',
                    'host.state.soft_state',
                    'redundancy_group.state.failed'
                ]
            ),
            'last_state_change' => new Expression(
                'CASE WHEN %s IS NOT NULL THEN %s WHEN %s IS NOT NULL THEN %s ELSE %s END',
                [
                    'service_id',
                    'service.state.last_state_change',
                    'host_id',
                    'host.state.last_state_change',
                    'redundancy_group.state.last_state_change'
                ]
            )
        ];
    }

    public function getSearchColumns(): array
    {
        return [
            'host.name_ci',
            'service.name_ci',
            'redundancy_group.display_name'
        ];
    }

    public function getDefaultSort(): string
    {//TODO: this breaks some host/service detail view, having problematic_parent
        return 'severity desc, last_state_change desc';
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Binary([
            'id',
            'host_id',
            'service_id',
            'redundancy_group_id'
        ]));
        $behaviors->add(new ReRoute([
            'child' => 'to.from',
            'parent' => 'from.to'
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

        // TODO: This self join is only a work-around as when selecting nodes and filtering by child or parent,
        //       the ORM wants to join the base table as usual in case a sub-query is used. Though, in this case
        //       resolving e.g. child to "to.from" is reversed in a sub-query to "from.to" and the ORM does not
        //       detect that "to" is already the link to the base table.
        //       Given the path "dependency_node.to.from.host", the sub-query uses "host.from.to.dependency_node".
        //       "to.dependency_node" is the crucial part, as "dependency_node" is said self-join.
        $relations->hasOne('dependency_node', self::class)
            ->setForeignKey('id');
    }
}
