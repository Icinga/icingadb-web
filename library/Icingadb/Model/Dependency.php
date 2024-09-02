<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\Bitmask;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Orm\Relations;

/**
 * Dependency model.
 *
 * @property string $id
 * @property string $name
 * @property string $display_name
 * @property ?string $redundancy_group_id
 * @property bool $disable_checks
 * @property bool $disable_notifications
 * @property bool $ignore_soft_states
 * @property ?string $timeperiod_id
 * @property string[] $states
 *
 * @property (?Timeperiod)|Query $timeperiod
 * @property (?RedundancyGroup)|Query $redundancy_group
 * @property (?DependencyState)|Query $state
 * @property DependencyEdge|Query $edge
 */
class Dependency extends Model
{
    public function getTableName(): string
    {
        return 'dependency';
    }

    public function getKeyName(): string
    {
        return 'id';
    }

    public function getColumns(): array
    {
        return [
            'name',
            'display_name',
            'redundancy_group_id',
            'disable_checks',
            'disable_notifications',
            'ignore_soft_states',
            'timeperiod_id',
            'states'
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Binary([
            'id',
            'redundancy_group_id',
            'timeperiod_id'
        ]));
        $behaviors->add(new BoolCast([
            'disable_checks',
            'disable_notifications',
            'ignore_soft_states'
        ]));
        $behaviors->add(new Bitmask([
            'states' => [
                'ok'        => 1,
                'warning'   => 2,
                'critical'  => 4,
                'unknown'   => 8,
                'up'        => 16,
                'down'      => 32
            ],
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('timeperiod', Timeperiod::class)
            ->setJoinType('LEFT');
        $relations->belongsTo('redundancy_group', RedundancyGroup::class)
            ->setJoinType('LEFT');

        $relations->hasOne('state', DependencyState::class)
            ->setJoinType('LEFT');

        $relations->hasOne('edge', DependencyEdge::class);
    }
}
