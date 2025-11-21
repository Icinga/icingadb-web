<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use Icinga\Module\Icingadb\Common\Model;
use ipl\Orm\Relations;

/**
 * @property string $id
 * @property string $timeperiod_id
 * @property string $override_id
 * @property string $environment_id
 */
class TimeperiodOverrideInclude extends Model
{
    public function getTableName()
    {
        return 'timeperiod_override_include';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'timeperiod_id',
            'override_id',
            'environment_id'
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'timeperiod_id',
            'override_id',
            'environment_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('timeperiod', Timeperiod::class);
        // TODO: `timeperiod` cannot be used again, find a better name
        $relations->belongsTo('timeperiod', Timeperiod::class)
            ->setCandidateKey('override_id');
    }
}
