<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

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
        return ['environment_id', 'id'];
    }

    public function getColumns()
    {
        return [
            'icon_image',
            'environment_id'
        ];
    }

    public function getMetaData()
    {
        return [
            'icon_image'        => t('Icon Image'),
            'environment_id'    => t('Icon Image Environment Id')
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);

        $relations->hasMany('host', Host::class);
        $relations->hasMany('service', Service::class);
    }
}
