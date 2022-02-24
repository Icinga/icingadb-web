<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Hook;

use Icinga\Application\Icinga;
use Icinga\Module\Icingadb\Hook\Common\HookUtils;
use Icinga\Web\Session;

abstract class IcingadbSupportHook
{
    use HookUtils;

    /** @var string key name of preference */
    const PREFERENCE_NAME = 'icingadb.as_backend';

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
        return (bool) Session::getSession()
            ->getNamespace('icingadb')
            ->get(self::PREFERENCE_NAME, false);
    }

    /**
     * Whether to use icingadb as the backend
     *
     * @return bool Returns true if monitoring module is disabled or icingadb is selected as backend, false otherwise.
     */
    final public static function useIcingaDbAsBackend(): bool
    {
        return ! Icinga::app()->getModuleManager()->hasEnabled('monitoring')
            || self::isIcingaDbSetAsPreferredBackend();
    }
}
