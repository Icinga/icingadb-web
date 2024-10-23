<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Orm\Relations;
use ipl\Sql\Connection;
use ipl\Sql\Select;

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
 */
class DependencyNode extends Model
{
    public static function on(Connection $db): Query
    {
        $query = parent::on($db);

        $query->on(Query::ON_SELECT_ASSEMBLED, function (Select $select) {
            $where = $select->getWhere();
            $filter = 'dependency_node.id IN (?)';
            if (! isset($where[1][0][1][0][1][$filter])) {
                return;
            }

            $subQuery = $where[1][0][1][0][1][$filter];
            $select->resetWhere();

            $joins = $subQuery->getJoin();
            $subQuery->resetJoin();

            foreach ($joins as $join) {
                $condition = $join[2];
                if ($condition[1][0] === 'sub_host_dependency_node.host_id = sub_host.id') {
                    $condition[1][0] = sprintf(
                        '%s AND sub_host_dependency_node.service_id IS NULL',
                        $condition[1][0]
                    );
                }

                $subQuery->join($join[1], $condition);
            }

            $select->where([$filter => $subQuery]);
        });

        return $query;
    }

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
