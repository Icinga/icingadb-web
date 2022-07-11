<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\ActionAndNoteUrl;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class NotesUrl extends Model
{
    public function getTableName()
    {
        return 'notes_url';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'notes_url',
            'environment_id'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'notes_url'         => t('Notes Url'),
            'environment_id'    => t('Environment Id')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new ActionAndNoteUrl(['notes_url']));

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
            ->setForeignKey('notes_url_id');
        $relations->hasMany('service', Service::class)
            ->setCandidateKey('id')
            ->setForeignKey('notes_url_id');
    }
}
