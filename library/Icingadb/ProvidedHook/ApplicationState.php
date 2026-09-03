<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\ProvidedHook;

use Exception;
use Icinga\Application\Hook\ApplicationStateHook;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Common\IcingaRedis;
use Icinga\Module\Icingadb\Model\Instance;
use Icinga\Web\Session;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;

class ApplicationState extends ApplicationStateHook
{
    use Auth;
    use Database;
    use Translation;

    public function collectMessages()
    {
        $session = Session::getSession()->getNamespace('icingadb');

        try {
            $lastIcingaHeartbeat = IcingaRedis::getLastIcingaHeartbeat();
        } catch (Exception $e) {
            $downSince = $session->get('redis.down-since');

            if ($downSince === null) {
                $downSince = time();
                $session->set('redis.down-since', $downSince);
            }

            $this->addError(
                'icingadb/redis-down',
                $downSince,
                sprintf($this->translate("Can't connect to Redis: %s"), $e->getMessage())
            );

            return;
        }

        $instance = Instance::on($this->getDb())
            ->with(['endpoint'])
            ->filter(Filter::equal('responsible', true))
            ->orderBy('heartbeat', 'desc')
            ->first();

        if ($instance === null) {
            $noInstanceSince = $session->get('icingadb.no-instance-since');

            if ($noInstanceSince === null) {
                $noInstanceSince = time();
                $session->set('icingadb.no-instance-since', $noInstanceSince);
            }

            $this->addError(
                'icingadb/no-instance',
                $noInstanceSince,
                $this->translate(
                    'It seems that Icinga DB is not running.'
                    . ' Make sure Icinga DB is running and writing into the database.'
                )
            );

            return;
        } else {
            $session->delete('db.no-instance-since');
        }

        $outdatedDbHeartbeat = $instance->heartbeat->getTimestamp() < time() - 60;

        if ($lastIcingaHeartbeat === null) {
            $missingSince = $session->get('redis.heartbeat-missing-since');

            if ($missingSince === null) {
                $missingSince = time();
                $session->set('redis.heartbeat-missing-since', $missingSince);
            }

            $lastIcingaHeartbeat = $missingSince;
        } else {
            $session->delete('redis.heartbeat-missing-since');
        }

        if ($outdatedDbHeartbeat && $instance->heartbeat->getTimestamp() > $lastIcingaHeartbeat) {
            $this->addError(
                'icingadb/redis-outdated',
                $lastIcingaHeartbeat,
                $this->translate('Redis is outdated. Make sure Icinga 2 is running and connected to Redis.')
            );
        } elseif ($outdatedDbHeartbeat) {
            $this->addError(
                'icingadb/icingadb-down',
                $instance->heartbeat->getTimestamp(),
                $this->translate(
                    'It seems that Icinga DB is not running.'
                    . ' Make sure Icinga DB is running and writing into the database.'
                )
            );
        }

        $session->delete('redis.down-since');

        if (isset($instance->notifications_healthy) && ! $instance->notifications_healthy) {
            if (
                ! $this->getAuth()->hasPermission('icingadb/notifications/manage')
                && ! $this->getAuth()->hasPermission('icingadb/notifications/subscribe')
            ) {
                // Do not show this error to users who do not rely on it

                return;
            }

            $unhealthySince = $session->get('notifications.unhealthy-since');
            if ($unhealthySince === null) {
                $unhealthySince = time();
                $session->set('notifications.unhealthy-since', $unhealthySince);
            }

            $this->addError(
                'icingadb/notifications-unhealthy',
                $unhealthySince,
                $this->translate(
                    'Notification transmission has been interrupted.'
                    . ' Make sure Icinga DB is able to connect to Icinga Notifications.'
                )
            );
        } else {
            $session->delete('notifications.unhealthy-since');
        }
    }
}
