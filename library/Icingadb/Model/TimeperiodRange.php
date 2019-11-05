<?php

namespace Icinga\Module\Eagle\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class TimeperiodRange extends Model
{
    public function getTableName()
    {
        return 'timeperiod_range';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'timeperiod_id',
            'range_key',
            'environment_id',
            'range_value'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('timeperiod', Timeperiod::class);
    }
}
