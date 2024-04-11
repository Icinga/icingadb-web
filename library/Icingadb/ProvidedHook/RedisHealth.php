<?php

// Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2

namespace Icinga\Module\Icingadb\ProvidedHook;

use Exception;
use Icinga\Application\Hook\HealthHook;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Common\IcingaRedis;
use Icinga\Module\Icingadb\Model\Instance;

class RedisHealth extends HealthHook
{
    use Database;

    public function getName(): string
    {
        return 'Redis';
    }

    public function checkHealth()
    {
        try {
            $lastIcingaHeartbeat = IcingaRedis::getLastIcingaHeartbeat();
            if ($lastIcingaHeartbeat === null) {
                $lastIcingaHeartbeat = time();
            }

            $instance = Instance::on($this->getDb())->columns('heartbeat')->first();

            if ($instance === null) {
                $this->setState(self::STATE_UNKNOWN);
                $this->setMessage(t(
                    'Can\'t check Redis: Icinga DB is not running or not writing into the database'
                    . ' (make sure the icinga feature "icingadb" is enabled)'
                ));

                return;
            }

            $outdatedDbHeartbeat = $instance->heartbeat->getTimestamp() < time() - 60;
            if (! $outdatedDbHeartbeat || $instance->heartbeat->getTimestamp() <= $lastIcingaHeartbeat) {
                $this->setState(self::STATE_OK);
                $this->setMessage(t('Redis available and up to date.'));
            } elseif ($instance->heartbeat->getTimestamp() > $lastIcingaHeartbeat) {
                $this->setState(self::STATE_CRITICAL);
                $this->setMessage(t('Redis outdated. Make sure Icinga 2 is running and connected to Redis.'));
            }
        } catch (Exception $e) {
            $this->setState(self::STATE_CRITICAL);
            $this->setMessage(sprintf(t("Can't connect to Redis: %s"), $e->getMessage()));
        }
    }
}
