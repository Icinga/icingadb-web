<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook;

use Exception;
use Icinga\Application\Hook\ApplicationStateHook;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Common\IcingaRedis;
use Icinga\Module\Icingadb\Model\Instance;
use Icinga\Web\Session;

class ApplicationState extends ApplicationStateHook
{
    use Database;
    use IcingaRedis;

    public function collectMessages()
    {
        $instance = Instance::on($this->getDb())->with(['endpoint'])->first();

        if ($instance === null) {
            $noInstanceSince = Session::getSession()
                ->getNamespace('icingadb')->get('icingadb.no-instance-since');

            if ($noInstanceSince === null) {
                $noInstanceSince = time();
                Session::getSession()
                    ->getNamespace('icingadb')->set('icingadb.no-instance-since', $noInstanceSince);
            }

            $this->addError(
                'icingadb/no-instance',
                $noInstanceSince,
                t(
                    'It seems that Icinga DB is not running.'
                    . ' Make sure Icinga DB is running and writing into the database.'
                )
            );

            return;
        } else {
            Session::getSession()->getNamespace('icingadb')->delete('db.no-instance-since');
        }

        $outdatedDbHeartbeat = $instance->heartbeat < time() - 60;

        try {
            $redis = $this->getIcingaRedis();

            Session::getSession()->getNamespace('icingadb')->delete('redis.down-since');

            $lastIcingaHeartbeat = $this->getLastIcingaHeartbeat($redis);
            if ($lastIcingaHeartbeat === false) {
                if ($outdatedDbHeartbeat) {
                    $this->addError(
                        'icingadb/icingadb-down',
                        $instance->heartbeat,
                        t(
                            'It seems that Icinga DB is not running.'
                            . ' Make sure Icinga DB is running and writing into the database.'
                        )
                    );
                }

                return;
            } elseif ($lastIcingaHeartbeat === null) {
                $missingSince = Session::getSession()
                    ->getNamespace('icingadb')->get('redis.heartbeat-missing-since');

                if ($missingSince === null) {
                    $missingSince = time();
                    Session::getSession()
                        ->getNamespace('icingadb')->set('redis.heartbeat-missing-since', $missingSince);
                }

                $lastIcingaHeartbeat = $missingSince;
            } else {
                Session::getSession()->getNamespace('icingadb')->delete('redis.heartbeat-missing-since');
            }

            switch (true) {
                case $outdatedDbHeartbeat && $instance->heartbeat > $lastIcingaHeartbeat:
                    $this->addError(
                        'icingadb/redis-outdated',
                        $lastIcingaHeartbeat,
                        t('Icinga Redis is outdated. Make sure Icinga 2 is running and connected to Redis.')
                    );

                    break;
                case $outdatedDbHeartbeat:
                    $this->addError(
                        'icingadb/icingadb-down',
                        $instance->heartbeat,
                        t(
                            'It seems that Icinga DB is not running.'
                            . ' Make sure Icinga DB is running and writing into the database.'
                        )
                    );

                    break;
            }
        } catch (Exception $e) {
            $downSince = Session::getSession()->getNamespace('icingadb')->get('redis.down-since');

            if ($downSince === null) {
                $downSince = time();
                Session::getSession()->getNamespace('icingadb')->set('redis.down-since', $downSince);
            }

            $this->addError(
                'icingadb/redis-down',
                $downSince,
                sprintf(t("Can't connect to Icinga Redis: %s"), $e->getMessage())
            );
        }
    }
}
