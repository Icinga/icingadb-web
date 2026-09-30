<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Model;

use DateTime;
use Icinga\Module\Icingadb\Common\Model;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

/**
 * Model for table `alert_history`
 *
 * @property string $id
 * @property string $history_id
 * @property string $environment_id
 * @property ?string $contact_name
 * @property ?string $contactgroup_name
 * @property ?string $schedule_name
 * @property ?string $channel_name
 * @property DateTime $triggered_at
 * @property string $event_message
 */
class AlertHistory extends Model
{
    public function getTableName(): string
    {
        return 'alert_history';
    }

    public function getKeyName(): string
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'history_id',
            'environment_id',
            'contact_name',
            'contactgroup_name',
            'schedule_name',
            'channel_name',
            'triggered_at',
            'event_message'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'environment_id'    => t('Environment Id'),
            'contact_name'      => t('Alert Contact Name'),
            'contactgroup_name' => t('Alert Contact Group Name'),
            'schedule_name'     => t('Alert Schedule Name'),
            'channel_name'      => t('Alert Channel Name'),
            'triggered_at'      => t('Alert Triggered At'),
            'event_message'     => t('Alert Event Message')
        ];
    }

    public function getDefaultSort()
    {
        return 'alert_history.triggered_at desc';
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary(['id', 'history_id', 'environment_id']));
        $behaviors->add(new MillisecondTimestamp(['triggered_at']));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('history', History::class);
    }
}
