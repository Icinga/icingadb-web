<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class IconImage extends Model
{
    public function getTableName()
    {
        return 'icon_image';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'icon_image',
            'environment_id'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'icon_image'        => t('Icon Image'),
            'environment_id'    => t('Icon Image Environment Id')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'environment_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);

        $relations->hasMany('host', Host::class);
        $relations->hasMany('service', Service::class);
    }
}
