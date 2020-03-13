<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

trait Auth
{
    public function getAuth()
    {
        return \Icinga\Authentication\Auth::getInstance();
    }
}
