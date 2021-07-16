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
    use IcingaRedis;

    public function getName()
    {
        return 'Icinga Redis';
    }

    public function checkHealth()
    {
        try {
            $redis = $this->getIcingaRedis();

            $lastIcingaHeartbeat = $this->getLastIcingaHeartbeat($redis);
            if ($lastIcingaHeartbeat === null) {
                $lastIcingaHeartbeat = time();
            }

            $instance = Instance::on($this->getDb())->columns('heartbeat')->first();
            $outdatedDbHeartbeat = $instance->heartbeat < time() - 60;
            if (! $outdatedDbHeartbeat || $instance->heartbeat <= $lastIcingaHeartbeat) {
                $this->setState(self::STATE_OK);
                $this->setMessage(t('Icinga Redis available and up to date.'));
            } elseif ($instance->heartbeat > $lastIcingaHeartbeat) {
                $this->setState(self::STATE_CRITICAL);
                $this->setMessage(t('Icinga Redis outdated. Make sure Icinga 2 is running and connected to Redis.'));
            }
        } catch (Exception $e) {
            $this->setState(self::STATE_UNKNOWN);
            $this->setMessage(sprintf(t("Can't connect to Icinga Redis: %s"), $e->getMessage()));
        }
    }
}
