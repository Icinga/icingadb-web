<?php

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
        $this->checkDatabase();
        $this->checkRedis();
    }

    private function checkDatabase()
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
                mt(
                    'icingadb',
                    'It seems that Icinga DB is not running.'
                    . ' Make sure Icinga DB is running and writing into the database.'
                )
            );
        } else {
            Session::getSession()->getNamespace('icingadb')->delete('db.no-instance-since');

            if ($instance->heartbeat < time() - 60) {
                $this->addError(
                    'icingadb/icingadb-down',
                    $instance->heartbeat,
                    mt(
                        'icingadb',
                        'It seems that Icinga DB is not running.'
                        . ' Make sure Icinga DB is running and writing into the database.'
                    )
                );
            }
        }
    }

    private function checkRedis()
    {
        try {
            $redis = $this->getIcingaRedis();

            Session::getSession()->getNamespace('icingadb')->delete('redis.down-since');

            $lastIcingaHeartbeat = $this->getLastIcingaHeartbeat($redis);

            switch (true) {
                /** @noinspection PhpMissingBreakStatementInspection */
                case $lastIcingaHeartbeat === null:
                    $missingSince = Session::getSession()
                        ->getNamespace('icingadb')->get('redis.heartbeat-missing-since');

                    if ($missingSince === null) {
                        $missingSince = time();
                        Session::getSession()
                            ->getNamespace('icingadb')->set('redis.heartbeat-missing-since', $missingSince);
                    }

                    $lastIcingaHeartbeat = $missingSince;
                    // Fallthrough
                case $lastIcingaHeartbeat <= time() - 60:
                    $this->addError(
                        'icingadb/redis-outdated',
                        $lastIcingaHeartbeat,
                        mt(
                            'icingadb',
                            'Icinga Redis is outdated. Make sure Icinga 2 is running and connected to Redis.'
                        )
                    );

                    break;
                default:
                    Session::getSession()->getNamespace('icingadb')->delete('redis.heartbeat-missing-since');
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
                mt('icingadb', sprintf("Can't connect to Icinga Redis: %s", $e->getMessage()))
            );
        }
    }
}
