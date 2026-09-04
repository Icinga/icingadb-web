<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Common\Model;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behaviors;
use ipl\Orm\Query;
use ipl\Orm\Relations;

/**
 * @property string $env_key
 * @property string $env_value
 * @property bool $locked
 * @property string $endpoint_id
 * @property string $environment_id
 *
 * @property Environment|Query<Environment> $environment
 */
class Config extends Model
{
    public function getTableName(): string
    {
        return 'icingadb_config';
    }

    public function getKeyName(): array
    {
        return ['environment_id', 'endpoint_id', 'env_key'];
    }

    public function getColumns(): array
    {
        return [
            'env_key',
            'env_value',
            'locked',
            'endpoint_id',
            'environment_id'
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new BoolCast(['locked']));
        $behaviors->add(new Binary(['endpoint_id', 'environment_id']));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('environment', Environment::class);
    }
}
