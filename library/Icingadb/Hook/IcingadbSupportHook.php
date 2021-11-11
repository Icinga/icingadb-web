<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Hook;

use Icinga\Application\Icinga;
use Icinga\Authentication\Auth;
use Icinga\Module\Icingadb\Hook\Common\HookUtils;

abstract class IcingadbSupportHook
{
    use HookUtils;

    /** @var string key name of preference */
    const PREFERENCE_NAME = 'icingadb_as_backend';

    /**
     * Return whether your module supports IcingaDB or not
     *
     * @return bool
     */
    public function supportsIcingaDb(): bool
    {
        return true;
    }

    /**
     * Whether icingadb is set as the preferred backend in preferences
     *
     * @return bool Return true if icingadb is set as backend, false otherwise
     */
    final public static function isIcingaDbSetAsPreferredBackend(): bool
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
    final public static function useIcingaDbAsBackend(): bool
    {
        return ! Icinga::app()->getModuleManager()->hasEnabled('monitoring') || self::isSetAsBackend();
    }
}
