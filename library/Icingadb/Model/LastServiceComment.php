<?php

// SPDX-FileCopyrightText: 2023 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Relations;

class LastServiceComment extends Comment
{
    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('zone', Zone::class);
        $relations->belongsTo('state', ServiceState::class)
            ->setForeignKey('last_comment_id')
            ->setCandidateKey('id');
    }
}
