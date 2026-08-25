<?php

// SPDX-FileCopyrightText: 2024 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use Icinga\Module\Icingadb\Common\Model;
use ipl\Orm\Query;
use ipl\Orm\Relations;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Sql\Filter\Exists;
use ipl\Stdlib\Filter;

/**
 * Dependency node model.
 *
 * @property string $id
 * @property string $environment_id
 * @property ?string $host_id
 * @property ?string $service_id
 * @property ?string $redundancy_group_id
 * @property string $name
 * @property string $severity
 * @property string $state
 * @property string $last_state_change
 *
 * @property (?Host)|Query $host
 * @property (?Service)|Query $service
 * @property (?RedundancyGroup)|Query $redundancy_group
 * @property Query<DependencyNode> $parent
 * @property Query<DependencyNode> $child
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
            'environment_id',
            'host_id',
            'service_id',
            'redundancy_group_id',
            'name' => new Expression(
                'COALESCE(%s, %s, %s)',
                ['service.display_name', 'host.display_name', 'redundancy_group.display_name']
            ),
            'severity' => new Expression(
                "COALESCE(%s, %s, CASE WHEN %s = 'y' THEN 1 ELSE 0 END)",
                ['service.state.severity', 'host.state.severity', 'redundancy_group.state.failed']
            ),
            'state' => new Expression(
                "COALESCE(%s, %s, CASE WHEN %s = 'y' THEN 1 ELSE 0 END)",
                ['service.state.soft_state', 'host.state.soft_state', 'redundancy_group.state.failed']
            ),
            'last_state_change' => new Expression(
                'COALESCE(%s, %s, %s)',
                [
                    'service.state.last_state_change',
                    'host.state.last_state_change',
                    'redundancy_group.state.last_state_change'
                ]
            ),
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

    public function getDefaultSort(): array
    {
        return ['severity DESC', 'last_state_change DESC'];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'host_id',
            'service_id',
            'redundancy_group_id'
        ]));
        $behaviors->add(new ReRoute([
            'to' => 'parent', // Compatibility with dependencies < v1.0.4 only
            'hostgroup' => 'host.hostgroup',
            'servicegroup' => 'service.servicegroup'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('host', Host::class)
            ->setReverseName('dependency_node')
            ->setJoinType('LEFT');
        $relations->belongsTo('service', Service::class)
            ->setReverseName('dependency_node')
            ->setJoinType('LEFT');
        $relations->belongsTo('redundancy_group', RedundancyGroup::class)
            ->setReverseName('dependency_node')
            ->setJoinType('LEFT');

        $relations->belongsToMany('parent', DependencyNode::class)
            ->through(DependencyEdge::class)
            ->setForeignKey('from_node_id')
            ->setTargetForeignKey('to_node_id')
            ->setReverseName('child')
            ->setJoinType('LEFT');
        $relations->belongsToMany('child', DependencyNode::class)
            ->through(DependencyEdge::class)
            ->setForeignKey('to_node_id')
            ->setTargetForeignKey('from_node_id')
            ->setReverseName('parent')
            ->setJoinType('LEFT');
    }

    /**
     * Get the query with only (direct) parents or children of the given host id.
     *
     * @param string $hostId Host id to fetch parents or children for
     * @param Connection $db The database connection
     * @param bool $fetchParents Fetch parents if true, children otherwise
     *
     * @return Query
     *
     * @deprecated Use {@see static::on()} instead and filter for `child|parent.host.id`.
     */
    public static function forHost(string $hostId, Connection $db, bool $fetchParents = false): Query
    {
        return static::on($db)
            ->filter(Filter::equal(
                sprintf('%s.host.id', $fetchParents ? 'child' : 'parent'),
                $hostId
            ));
    }
}
