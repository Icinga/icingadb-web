<?php

namespace Icinga\Module\Icingadb\Common;

use Exception;
use Icinga\Application\Config;
use Redis;

trait IcingaRedis
{
    /** @var Redis Connection to the Icinga Redis */
    private $redis;

    /**
     * Get the connection to the Icinga Redis
     *
     * @return Redis
     *
     * @throws Exception
     */
    public function getIcingaRedis()
    {
        if ($this->redis === null) {
            try {
                $primaryRedis = $this->getPrimaryRedis();
            } catch (Exception $e) {
                $secondaryRedis = $this->getSecondaryRedis();

                if ($secondaryRedis === null) {
                    throw $e;
                }

                $this->redis = $secondaryRedis;

                return $this->redis;
            }

            $primaryTimestamp = $this->getLastIcingaHeartbeat($primaryRedis);

            if ($primaryTimestamp <= time() - 60) {
                $secondaryRedis = $this->getSecondaryRedis();

                if ($secondaryRedis === null) {
                    $this->redis = $primaryRedis;

                    return $this->redis;
                }

                $secondaryTimestamp = $this->getLastIcingaHeartbeat($secondaryRedis);

                if ($secondaryTimestamp > $primaryTimestamp) {
                    $this->redis = $secondaryRedis;
                } else {
                    $this->redis = $primaryRedis;
                }
            } else {
                $this->redis = $primaryRedis;
            }
        }

        return $this->redis;
    }

    public function getLastIcingaHeartbeat(Redis $redis)
    {
        $rs = $redis->xRead(['icinga:stats' => 0], 1);

        if (empty($rs)) {
            return null;
        }

        $stats = array_pop($rs['icinga:stats']);

        return $stats['timestamp'] / 1000;
    }

    private function getPrimaryRedis()
    {
        $config = Config::module('icingadb')->getSection('redis1');
        $redis = new Redis();

        $redis->connect(
            $config->get('host', 'redis'),
            $config->get('port', 6380)
        );

        return $redis;
    }

    private function getSecondaryRedis()
    {
        $config = Config::module('icingadb')->getSection('redis2');
        $redis = new Redis();

        $host = $config->host;

        if (empty($host)) {
            return null;
        }

        $redis->connect(
            $host,
            $config->get('port', 6380)
        );

        return $redis;
    }
}
