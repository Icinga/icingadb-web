<?php

namespace Icinga\Module\Icingadb\Common;

trait Auth {
    public function getAuth()
    {
        return \Icinga\Authentication\Auth::getInstance();
    }
}
