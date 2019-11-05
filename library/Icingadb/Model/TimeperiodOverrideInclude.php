<?php

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

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

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('timeperiod', Timeperiod::class);
        // TODO: `timeperiod` cannot be used again, find a better name
        $relations->belongsTo('timeperiod', Timeperiod::class)
            ->setCandidateKey('override_id');
    }
}
