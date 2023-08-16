<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Tacticalline extends Model
{
    public function getTableName()
    {
        return 'environment';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'environment_id'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'environment_id'        => t('Environment Id')
        ];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getDefaultSort()
    {
        return 'id';
    }

    public function createBehaviors(Behaviors $behaviors)
    {

        $behaviors->add(new Binary([
            'environment_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('zone', Zone::class);

        $relations->belongsToMany('customvar', Customvar::class)
            ->through(HostgroupCustomvar::class);
        $relations->belongsToMany('customvar_flat', CustomvarFlat::class)
            ->through(HostgroupCustomvar::class);
        $relations->belongsToMany('vars', Vars::class)
            ->through(HostgroupCustomvar::class);
        $relations->belongsToMany('host', Host::class)
            ->through(HostgroupMember::class);
        $relations->belongsToMany('service', Service::class)
            ->through(HostgroupMember::class);
    }
}
