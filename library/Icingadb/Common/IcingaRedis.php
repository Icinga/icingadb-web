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
     * @param  Config|null  $config Override the configured Redis.
     *
     * @return Redis
     *
     * @throws Exception
     */
    public function getIcingaRedis(Config $config = null)
    {
        if ($this->redis === null) {
            try {
                $primaryRedis = $this->getPrimaryRedis($config);
            } catch (Exception $e) {
                $secondaryRedis = $this->getSecondaryRedis($config);

                if ($secondaryRedis === null) {
                    throw $e;
                }

                $this->redis = $secondaryRedis;

                return $this->redis;
            }

            $primaryTimestamp = $this->getLastIcingaHeartbeat($primaryRedis);

            if ($primaryTimestamp <= time() - 60) {
                $secondaryRedis = $this->getSecondaryRedis($config);

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

    public static function getLastIcingaHeartbeat(Redis $redis)
    {
        // https://github.com/predis/predis/issues/607#event-3640855190
        $rs = $redis->executeRaw(['XREAD', 'COUNT', '1', 'STREAMS', 'icinga:stats', '0']);

        if (! is_array($rs)) {
            return null;
        }

        $fields = [];
        $key = null;

        foreach ($rs[0][1][0][1] as $kv) {
            if ($key === null) {
                $key = $kv;
            } else {
                $fields[$key] = $kv;
                $key = null;
            }
        }

        return $fields['timestamp'] / 1000;
    }

    private static function getPrimaryRedis(Config $config = null)
    {
        if ($config === null) {
            $config = Config::module('icingadb');
        }

        $section = $config->getSection('redis1');

        $redis = new Redis([
            'host'      => $section->get('host', 'localhost'),
            'port'      => $section->get('port', 6380),
            'timeout'   => 0.5
        ] + static::getTlsParams($config));

        $redis->ping();

        return $redis;
    }

    private static function getSecondaryRedis(Config $config = null)
    {
        if ($config === null) {
            $config = Config::module('icingadb');
        }

        $section = $config->getSection('redis2');
        $host = $section->host;

        if (empty($host)) {
            return null;
        }

        $redis = new Redis([
            'host'      => $host,
            'port'      => $section->get('port', 6380),
            'timeout'   => 0.5
        ] + static::getTlsParams($config));

        $redis->ping();

        return $redis;
    }

    private static function getTlsParams(Config $config)
    {
        $config = $config->getSection('redis');

        if (! $config->get('tls', false)) {
            return [];
        }

        $ssl = [];
        $ca = $config->get('ca');

        if ($ca === null) {
            $ssl['verify_peer'] = false;
            $ssl['verify_peer_name'] = false;
        } else {
            $ssl['cafile'] = static::ensurePemOnDisk($ca);
        }

        $cert = $config->get('cert');
        $key = $config->get('key');

        if ($cert !== null && $key !== null) {
            $ssl['local_cert'] = static::ensurePemOnDisk($cert . PHP_EOL . $key);
        }

        return ['scheme' => 'tls', 'ssl' => $ssl];
    }

    private static function ensurePemOnDisk($pem)
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . sha1($pem);
        $target = $dir . DIRECTORY_SEPARATOR . 'file.pem';

        if (! file_exists($target)) {
            $temp = $dir . DIRECTORY_SEPARATOR . uniqid();

            try {
                mkdir($dir, 0700);
            } catch (Exception $_) {
            }

            file_put_contents($temp, $pem);
            rename($temp, $target);
        }

        return $target;
    }
}
