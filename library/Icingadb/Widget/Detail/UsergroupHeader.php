<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Model\Usergroup;

/**
 * @property Usergroup $object
 */
class UsergroupHeader extends UserHeader
{
    protected $defaultAttributes = ['class' => 'usergroup-header'];

    protected const BALL_CLASS_NAME = 'usergroup-ball';
}
