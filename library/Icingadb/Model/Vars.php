<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\FlattenedObjectVars;
use ipl\Orm\Behaviors;
use ipl\Sql\Connection;

class Vars extends CustomvarFlat
{
    /**
     * @internal Don't use. This model acts only as relation target and is not supposed to be directly used as query
     *           target. Use {@see CustomvarFlat} instead.
     */
    public static function on(Connection $_)
    {
        throw new \LogicException('Documentation says: DO NOT USE. Can\'t you read?');
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        parent::createBehaviors($behaviors);

        $behaviors->add(new FlattenedObjectVars());
    }
}
