<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Backend;
use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Defaults;
use Icinga\Module\Icingadb\Common\Model;
use ipl\Orm\Query;
use ipl\Orm\Relations;

/**
 * Redundancy group model.
 *
 * @property string $id
 * @property string $environment_id
 * @property string $display_name
 *
 * @property (?RedundancyGroupState)|Query $state
 * @property DependencyEdge|Query $from
 * @property DependencyEdge|Query $to
 *
 * @property RedundancyGroupSummary $summary
 */
class RedundancyGroup extends Model
{
    use Auth;

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
            'environment_id',
            'display_name'
        ];
    }

    public function getColumnDefinitions(): array
    {
        return [
            'display_name' => t('Redundancy Group Display Name')
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Binary([
            'id',
            'environment_id'
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

        $relations->hasOne('dependency_node', DependencyNode::class)->setJoinType('LEFT');

        $relations->belongsToMany('from', DependencyEdge::class)
            ->setTargetCandidateKey('from_node_id')
            ->setTargetForeignKey('id')
            ->through(DependencyNode::class);
        $relations->belongsToMany('to', DependencyEdge::class)
            ->setTargetCandidateKey('to_node_id')
            ->setTargetForeignKey('id')
            ->through(DependencyNode::class);
    }

    public function createDefaults(Defaults $defaults): void
    {
        $defaults->add('summary', function (RedundancyGroup $group) {
            $summary = RedundancyGroupSummary::for($group->id, Backend::getDb());

            $this->applyRestrictions($summary);

            return $summary->first();
        });
    }
}
