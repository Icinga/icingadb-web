<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Relations;

class LastHostComment extends Comment
{
    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('zone', Zone::class);
        $relations->belongsTo('state', HostState::class)
            ->setForeignKey('last_comment_id')
            ->setCandidateKey('id');
    }
}
