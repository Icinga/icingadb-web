<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Model;

use DateTime;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use Icinga\Module\Icingadb\Common\Model;

/**
 * Schema model
 *
 * @property string $id
 * @property int $version
 * @property DateTime $timestamp
 */
class Schema extends Model
{
    public function getTableName(): string
    {
        return 'icingadb_schema';
    }

    public function getKeyName(): string
    {
        return 'id';
    }

    public function getColumns(): array
    {
        return [
            'version',
            'timestamp'
        ];
    }

    public function getDefaultSort(): array
    {
        return ['timestamp desc'];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Binary(['id']));
        $behaviors->add(new MillisecondTimestamp(['timestamp']));
    }
}
