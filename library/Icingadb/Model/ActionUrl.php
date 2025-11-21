<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Common\Model;
use Icinga\Module\Icingadb\Model\Behavior\ActionAndNoteUrl;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

/**
 * @property string $id
 * @property string[] $action_url
 * @property string $environment_id
 */
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

    public function getColumnDefinitions()
    {
        return [
            'action_url'        => t('Action Url'),
            'environment_id'    => t('Environment Id')
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
