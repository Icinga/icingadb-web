<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Common\Model;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

/**
 * @property string $id
 * @property string $checkcommand_id
 * @property string $customvar_id
 * @property string $environment_id
 */
class CheckcommandCustomvar extends Model
{
    public function getTableName()
    {
        return 'checkcommand_customvar';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'checkcommand_id',
            'customvar_id',
            'environment_id'
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'checkcommand_id',
            'customvar_id',
            'environment_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('checkcommand', Checkcommand::class);
        $relations->belongsTo('customvar', Customvar::class);
        $relations->belongsTo('customvar_flat', CustomvarFlat::class)
            ->setCandidateKey('customvar_id')
            ->setForeignKey('customvar_id');
    }
}
