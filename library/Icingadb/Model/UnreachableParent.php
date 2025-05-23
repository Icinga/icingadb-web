<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use Icinga\Module\Icingadb\Model\UnreachableParent\ResultSet;
use InvalidArgumentException;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Orm\Relations;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Sql\Select;
use ipl\Stdlib\Filter;

/**
 * Class UnreachableParent
 *
 * @property string $id
 * @property int $level
 * @property string $child_id
 * @property ?string $host_id
 * @property ?string $service_id
 * @property ?string $redundancy_group_id
 * @property int $is_group_member
 *
 * @property (?Host)|Query $host
 * @property (?Service)|Query $service
 * @property (?RedundancyGroup)|Query $redundancy_group
 */
class UnreachableParent extends DependencyNode
{
    public function getTableName(): string
    {
        return 'unreachable_parent';
    }

    public function getKeyName(): string
    {
        return 'id';
    }

    public function getColumns(): array
    {
        return [
            'id',
            'child_id',
            'level',
            'host_id',
            'service_id',
            'redundancy_group_id',
            'is_group_member',
            '_has_problematic_parent'
        ];
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('host', Host::class)
            ->setJoinType('LEFT');
        $relations->belongsTo('service', Service::class)
            ->setJoinType('LEFT');
        $relations->belongsTo('redundancy_group', RedundancyGroup::class)
            ->setJoinType('LEFT');
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Binary([
            'id',
            'child_id',
            'host_id',
            'service_id',
            'redundancy_group_id'
        ]));

        $behaviors->add(new ReRoute([
            'hostgroup'     => 'host.hostgroup',
            'servicegroup'  => 'service.servicegroup'
        ]));
    }

    public static function on(Connection $db, Model $root = null): Query
    {
        if ($root === null) {
            throw new InvalidArgumentException('Root node must not be null');
        }

        $query = parent::on($db);
        $query->getSelectBase()->with(
            self::selectNodes($db, $root),
            'unreachable_parent',
            true
        );

        $query->filter(Filter::all(
            Filter::greaterThan('level', 0),
            Filter::equal('is_group_member', 0),
            Filter::any(
                Filter::any(
                    Filter::equal('service.state.affects_children', 'y'),
                    Filter::all(
                        // TODO: Remove this once implicit dependencies are properly implemented
                        Filter::equal('_has_problematic_parent', 0),
                        Filter::equal('service.state.is_reachable', 'n')
                    )
                ),
                Filter::all(
                    Filter::unlike('service_id', '*'),
                    Filter::equal('host.state.affects_children', 'y')
                ),
                Filter::all(
                    Filter::equal('redundancy_group.state.failed', 'y'),
                    Filter::equal('redundancy_group.state.is_reachable', 'y')
                )
            )
        ));

        $query->setResultSetClass(ResultSet::class);

        return $query;
    }

    private static function selectNodes(Connection $db, Model $root): Select
    {
        if ($db->getAdapter() instanceof Pgsql) {
            $binaryCast = "CAST('00000000000000000000' AS bytea20)";
            $booleanCast = "CAST('0' AS boolean)";
        } else {
            $binaryCast = "CAST('' AS binary(20))";
            $booleanCast = '0';
        }

        $rootQuery = DependencyNode::on($db)
            ->columns([
                'id' => 'id',
                'child_id' => 'id',
                'level' => new Expression('0'),
                'host_id' => 'host_id',
                'service_id' => new Expression("COALESCE(%s, $binaryCast)", ['service_id']),
                'redundancy_group_id' => new Expression($binaryCast),
                'is_group_member' => new Expression($booleanCast),
                '_has_problematic_parent' => new Expression($booleanCast)
            ])
            ->disableDefaultSort();
        if ($root instanceof Host) {
            $rootQuery->filter(Filter::all(
                Filter::equal('host_id', $root->id),
                Filter::unlike('service_id', '*')
            ));
        } elseif ($root instanceof Service) {
            $rootQuery->filter(Filter::all(
                Filter::equal('host_id', $root->host_id),
                Filter::equal('service_id', $root->id)
            ));
        } elseif ($root instanceof RedundancyGroup) {
            $rootQuery->filter(Filter::all(Filter::equal('redundancy_group_id', $root->id)));
        } else {
            throw new InvalidArgumentException('Root node must be either a host, service or a redundancy group');
        }

        $nodeQuery = DependencyEdge::on($db)
            ->columns([
                'id' => 'to_node_id',
                'child_id' => 'from_node_id',
                'level' => new Expression('urn.level + 1'),
                'host_id' => 'to.host_id',
                'service_id' => 'to.service_id',
                'redundancy_group_id' => 'to.redundancy_group_id',
                'is_group_member' => new Expression('urn.redundancy_group_id IS NOT NULL AND urn.level > 0'),
                '_has_problematic_parent' => new Expression(sprintf(
                    'EXISTS(SELECT 1 FROM %s immediate_parents'
                        . ' INNER JOIN %s state ON state.id = immediate_parents.dependency_edge_state_id'
                        . ' WHERE immediate_parents.from_node_id = %%s AND state.failed = \'y\')',
                    (new DependencyEdge())->getTableName(),
                    (new DependencyEdgeState())->getTableName()
                ), ['to_node_id'])
            ]);
        $nodeQuery->filter(Filter::equal('state.failed', 'y'));

        $nodeSelect = $nodeQuery->assembleSelect();

        // TODO: ipl-orm doesn't preserve key order :'(
        $columnsProperty = (new \ReflectionClass($nodeSelect))->getProperty('columns');
        $columnsProperty->setAccessible(true);
        $columnsProperty->setValue($nodeSelect, array_merge(
            [
                'id' => null,
                'child_id' => null,
                'level' => null,
                'host_id' => null,
                'service_id' => null,
                'redundancy_group_id' => null,
                'is_group_member' => null,
                '_has_problematic_parent' => null
            ],
            $nodeSelect->getColumns()
        ));

        return $rootQuery->assembleSelect()->union(
            $nodeSelect
                ->join(['urn' => 'unreachable_parent'], sprintf(
                    'urn.id = %s',
                    $nodeQuery
                        ->getResolver()
                        ->qualifyColumn(
                            'from_node_id',
                            $nodeQuery
                                ->getResolver()
                                ->getAlias($nodeQuery->getModel())
                        )
                ))
        );
    }
}
