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
 * @property string $range_key
 * @property string $environment_id
 * @property string $range_value
 */
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

    public function getColumnDefinitions()
    {
        return [
            'timeperiod_id'     => t('Timeperiod Id'),
            'range_key'         => t('Timeperiod Range Date(s)/Day'),
            'environment_id'    => t('Environment Id'),
            'range_value'       => t('Timeperiod Range Time')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'timeperiod_id',
            'environment_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('timeperiod', Timeperiod::class);
    }
}
