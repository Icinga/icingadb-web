<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb;

use Icinga\Application\Icinga;
use Icinga\Authentication\Auth;

class Icingadb
{
    /** @var string key name of preference */
    const PREFERENCE_NAME = 'icingadb_as_backend';

    /**
     * Whether icingadb is set as the backend source in preferences
     *
     * @return bool Return true if icingadb is set as backend, false otherwise
     */
    public static function isSetAsBackend(): bool
    {
        $webPreferences = Auth::getInstance()->getUser()->getPreferences()->get('icingaweb');
        if (! empty($webPreferences) && array_key_exists(static::PREFERENCE_NAME, $webPreferences)) {
            return (bool) $webPreferences[static::PREFERENCE_NAME];
        }

        return false;
    }

    /**
     * Whether to use icingadb as the backend
     *
     * @return bool Returns true if monitoring module is disabled or icingadb is selected as backend, false otherwise.
     */
    public static function useAsBackend()
    {
        return ! Icinga::app()->getModuleManager()->hasEnabled('monitoring') || self::isSetAsBackend();
    }
}
