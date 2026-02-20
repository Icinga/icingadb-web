<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Exception;
use Generator;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Predis\Client as Redis;

class IcingaRedis
{
    public const DEFAULT_HOST = 'localhost';

    public const DEFAULT_PORT = 6380;

    public const DEFAULT_DATABASE = 0;

    /** @var Redis Connection to the Icinga Redis */
    private $redis;

    /** @var bool true if no connection attempt was successful */
    private $redisUnavailable = false;

    /**
     * Get the singleton
     *
     * @deprecated Use {@see Backend::getRedis()} instead
     * @return static
     */
    public static function instance(): self
    {
        return Backend::getRedis();
    }

    /**
     * Get whether Redis is unavailable
     *
     * @return bool
     */
    public function isUnavailable(): bool
    {
        if (! $this->redisUnavailable && $this->redis === null) {
            try {
                $this->getConnection();
            } catch (Exception $_) {
                // getConnection already logs the error
            }
        }

        return $this->redisUnavailable;
    }

    /**
     * Get the connection to the Icinga Redis
     *
     * @return Redis
     *
     * @throws Exception
     */
    public function getConnection(): Redis
    {
        if ($this->redisUnavailable) {
            throw new Exception('Redis is still not available');
        } elseif ($this->redis === null) {
            try {
                $primaryRedis = $this->getPrimaryRedis();
            } catch (Exception $e) {
                try {
                    $secondaryRedis = $this->getSecondaryRedis();
                } catch (Exception $ee) {
                    $this->redisUnavailable = true;
                    Logger::error($ee);

                    throw $e;
                }

                if ($secondaryRedis === null) {
                    $this->redisUnavailable = true;

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

    /**
     * Fetch host states
     *
     * @param array $ids The host ids to fetch results for
     * @param array $columns The columns to include in the results
     *
     * @return Generator
     */
    public static function fetchHostState(array $ids, array $columns): Generator
    {
        return Backend::getRedis()->fetchState('icinga:host:state', $ids, $columns);
    }

    /**
     * Fetch service states
     *
     * @param array $ids The service ids to fetch results for
     * @param array $columns The columns to include in the results
     *
     * @return Generator
     */
    public static function fetchServiceState(array $ids, array $columns): Generator
    {
        return Backend::getRedis()->fetchState('icinga:service:state', $ids, $columns);
    }

    /**
     * Fetch object states
     *
     * @param string $key The object key to access
     * @param array $ids The object ids to fetch results for
     * @param array $columns The columns to include in the results
     *
     * @return Generator
     */
    protected function fetchState(string $key, array $ids, array $columns): Generator
    {
        try {
            $results = $this->getConnection()->hmget($key, $ids);
        } catch (Exception $_) {
            // The error has already been logged elsewhere
            return;
        }

        foreach ($results as $i => $json) {
            if ($json !== null) {
                $data = json_decode($json, true);
                $keyMap = array_fill_keys($columns, null);
                unset($keyMap['is_overdue']); // Is calculated by Icinga DB, not Icinga 2, hence it's never in redis

                yield $ids[$i] => array_intersect_key(array_merge($keyMap, $data), $keyMap);
            }
        }
    }

    /**
     * Get the last icinga heartbeat from redis
     *
     * @param Redis|null $redis
     *
     * @return float|int|null
     */
    public static function getLastIcingaHeartbeat(?Redis $redis = null)
    {
        if ($redis === null) {
            $redis = Backend::getRedis()->getConnection();
        }

        $stream = 'icinga:stats';

        $rs = $redis->xread(1, null, [$stream], '0');

        if (is_array($rs)) {
            $timestampKeyPos = array_search('timestamp', $rs[$stream][0][1], true);

            if ($timestampKeyPos !== false && isset($rs[$stream][0][1][$timestampKeyPos + 1])) {
                return $rs[$stream][0][1][$timestampKeyPos + 1] / 1000;
            }
        }

        return null;
    }

    /**
     * Get the primary redis instance
     *
     * @param Config|null $moduleConfig
     * @param Config|null $redisConfig
     *
     * @return Redis
     */
    public static function getPrimaryRedis(?Config $moduleConfig = null, ?Config $redisConfig = null): Redis
    {
        if ($moduleConfig === null) {
            $moduleConfig = Config::module('icingadb');
        }

        if ($redisConfig === null) {
            $redisConfig = Config::module('icingadb', 'redis');
        }

        $section = $redisConfig->getSection('redis1');

        $redis = new Redis([
            'host'      => $section->get('host', static::DEFAULT_HOST),
            'port'      => $section->get('port', static::DEFAULT_PORT),
            'database'  => $section->get('database', static::DEFAULT_DATABASE),
            'username'  => $section->get('username'),
            'password'  => $section->get('password'),
            'timeout'   => 0.5
        ] + self::getTlsParams($moduleConfig));

        $redis->ping();

        return $redis;
    }

    /**
     * Get the secondary redis instance if exists
     *
     * @param Config|null $moduleConfig
     * @param Config|null $redisConfig
     *
     * @return ?Redis
     */
    public static function getSecondaryRedis(?Config $moduleConfig = null, ?Config $redisConfig = null)
    {
        if ($moduleConfig === null) {
            $moduleConfig = Config::module('icingadb');
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
            'port'      => $section->get('port', static::DEFAULT_PORT),
            'database'  => $section->get('database', static::DEFAULT_DATABASE),
            'username'  => $section->get('username'),
            'password'  => $section->get('password'),
            'timeout'   => 0.5
        ] + self::getTlsParams($moduleConfig));

        $redis->ping();

        return $redis;
    }

    private static function getTlsParams(Config $config): array
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
