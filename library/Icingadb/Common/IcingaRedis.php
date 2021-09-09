<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Exception;
use Icinga\Application\Config;
use Predis\Client as Redis;

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
        // Predis doesn't support streams (yet).
        // https://github.com/predis/predis/issues/607#event-3640855190
        $rs = $redis->executeRaw(['XREAD', 'COUNT', '1', 'STREAMS', 'icinga:stats', '0']);

        if (! is_array($rs)) {
            return null;
        }

        $key = null;

        foreach ($rs[0][1][0][1] as $kv) {
            if ($key === null) {
                $key = $kv;
            } else {
                if ($key === 'timestamp') {
                    return $kv / 1000;
                }

                $key = null;
            }
        }

        return null;
    }

    public function getPrimaryRedis(Config $moduleConfig = null, Config $redisConfig = null)
    {
        if ($moduleConfig === null) {
            $moduleConfig = Config::module('icingadb');
        }

        if ($redisConfig === null) {
            $redisConfig = Config::module('icingadb', 'redis');
        }

        $section = $redisConfig->getSection('redis1');

        $redis = new Redis([
            'host'      => $section->get('host', 'localhost'),
            'port'      => $section->get('port', 6380),
            'timeout'   => 0.5
        ] + $this->getTlsParams($moduleConfig));

        $redis->ping();

        return $redis;
    }

    public function getSecondaryRedis(Config $moduleConfig = null, Config $redisConfig = null)
    {
        if ($moduleConfig === null) {
            $moduleConfig = Config::module('redis');
        }

        if ($redisConfig === null) {
            $redisConfig = Config::module('icingadb', 'redis');
        }

        $section = $redisConfig->getSection('redis2');
        $host = $section->host;

        if (empty($host)) {
            return null;
        }

        $redis = new Redis([
            'host'      => $host,
            'port'      => $section->get('port', 6380),
            'timeout'   => 0.5
        ] + $this->getTlsParams($moduleConfig));

        $redis->ping();

        return $redis;
    }

    private function getTlsParams(Config $config)
    {
        $config = $config->getSection('redis');

        if (! $config->get('tls', false)) {
            return [];
        }

        $ssl = [];

        if ($config->get('insecure')) {
            $ssl['verify_peer'] = false;
            $ssl['verify_peer_name'] = false;
        } else {
            $ca = $config->get('ca');

            if ($ca !== null) {
                $ssl['cafile'] = $ca;
            }
        }

        $cert = $config->get('cert');
        $key = $config->get('key');

        if ($cert !== null && $key !== null) {
            $ssl['local_cert'] = $cert;
            $ssl['local_pk'] = $key;
        }

        return ['scheme' => 'tls', 'ssl' => $ssl];
    }
}
