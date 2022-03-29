<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\ActionAndNoteUrl;
use Icinga\Module\Icingadb\Model\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ActionUrl extends Model
{
    public function getTableName()
    {
        return 'action_url';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'action_url',
            'environment_id'
        ];
    }

    public function getMetaData()
    {
        return [
            'action_url'        => t('Action Url'),
            'environment_id'    => t('Action Url Environment Id')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new ActionAndNoteUrl(['action_url']));

        $behaviors->add(new Binary([
            'id',
            'environment_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);

        $relations->hasMany('host', Host::class)
            ->setCandidateKey('id')
            ->setForeignKey('action_url_id');
        $relations->hasMany('service', Service::class)
            ->setCandidateKey('id')
            ->setForeignKey('action_url_id');
    }
}
