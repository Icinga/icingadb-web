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
 * @property string $argument_key
 * @property string $environment_id
 * @property string $properties_checksum
 * @property ?string $argument_value
 * @property ?int $argument_order
 * @property ?string $description
 * @property ?string $argument_key_override
 * @property string $repeat_key
 * @property string $required
 * @property ?string $set_if
 * @property ?string $separator
 * @property string $skip_key
 */
class CheckcommandArgument extends Model
{
    public function getTableName()
    {
        return 'checkcommand_argument';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'checkcommand_id',
            'argument_key',
            'environment_id',
            'properties_checksum',
            'argument_value',
            'argument_order',
            'description',
            'argument_key_override',
            'repeat_key',
            'required',
            'set_if',
            'separator',
            'skip_key'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'checkcommand_id'       => t('Checkcommand Id'),
            'argument_key'          => t('Checkcommand Argument Name'),
            'environment_id'        => t('Environment Id'),
            'properties_checksum'   => t('Checkcommand Argument Properties Checksum'),
            'argument_value'        => t('Checkcommand Argument Value'),
            'argument_order'        => t('Checkcommand Argument Position'),
            'description'           => t('Checkcommand Argument Description'),
            'argument_key_override' => t('Checkcommand Argument Actual Name'),
            'repeat_key'            => t('Checkcommand Argument Repeated'),
            'required'              => t('Checkcommand Argument Required'),
            'set_if'                => t('Checkcommand Argument Condition'),
            'skip_key'              => t('Checkcommand Argument Without Name')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'checkcommand_id',
            'environment_id',
            'properties_checksum'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('checkcommand', Checkcommand::class);
    }
}
