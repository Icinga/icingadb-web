<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use DateTime;
use Icinga\Module\Icingadb\Common\Icons;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use Icinga\Module\Icingadb\Common\Model;
use ipl\Orm\Query;
use ipl\Orm\Relations;
use ipl\Web\Widget\Icon;

/**
 * Redundancy group state model.
 *
 * @property string $id
 * @property string $environment_id
 * @property string $redundancy_group_id
 * @property bool $failed
 * @property bool $is_reachable
 * @property DateTime $last_state_change
 *
 * @property RedundancyGroup|Query $redundancy_group
 */
class RedundancyGroupState extends Model
{
    public function getTableName(): string
    {
        return 'redundancy_group_state';
    }

    public function getKeyName(): string
    {
        return 'id';
    }

    public function getColumns(): array
    {
        return [
            'environment_id',
            'redundancy_group_id',
            'failed',
            'is_reachable',
            'last_state_change'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'failed' => t('Redundancy Group Failed'),
            'is_reachable' => t('Redundancy Group Is Reachable'),
            'last_state_change' => t('Redundancy Group Last State Change')
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'redundancy_group_id'
        ]));
        $behaviors->add(new BoolCast([
            'failed',
            'is_reachable'
        ]));
        $behaviors->add(new MillisecondTimestamp([
            'last_state_change'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('redundancy_group', RedundancyGroup::class);
    }

    /**
     * Get the state text for the redundancy group state
     *
     * Do not use this method to label the state of a redundancy group.
     *
     * @return string
     */
    public function getStateText(): string
    {
        return $this->failed ? 'critical' : 'ok';
    }

    /**
     * Get the state icon
     *
     * @return ?Icon
     */
    public function getIcon(): ?Icon
    {
        if (! $this->is_reachable) {
            return new Icon(Icons::UNREACHABLE);
        }

        return null;
    }
}
