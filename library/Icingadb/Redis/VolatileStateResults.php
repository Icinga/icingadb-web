<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Redis;

use Exception;
use Generator;
use Icinga\Application\Benchmark;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\IcingaRedis;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Orm\Query;
use ipl\Orm\Resolver;
use ipl\Orm\ResultSet;
use Predis\Client;
use RuntimeException;

class VolatileStateResults extends ResultSet
{
    use Auth;

    /** @var Resolver */
    private $resolver;

    /** @var Client */
    private $redis;

    /** @var bool Whether Redis updates were applied */
    private $updatesApplied = false;

    public static function fromQuery(Query $query)
    {
        $self = parent::fromQuery($query);
        $self->resolver = $query->getResolver();

        try {
            $self->redis = IcingaRedis::instance()->getConnection();
        } catch (Exception $e) {
            // The error has already been logged
        }

        return $self;
    }

    /**
     * Get whether Redis is unavailable
     *
     * @return bool
     */
    public function isRedisUnavailable(): bool
    {
        return $this->redis === null;
    }

    public function current()
    {
        if ($this->redis && ! $this->updatesApplied && ! $this->isCacheDisabled) {
            $this->rewind();
        }

        return parent::current();
    }

    public function key(): int
    {
        if ($this->redis && ! $this->updatesApplied && ! $this->isCacheDisabled) {
            $this->rewind();
        }

        return parent::key();
    }

    public function rewind(): void
    {
        if ($this->redis && ! $this->updatesApplied && ! $this->isCacheDisabled) {
            $this->updatesApplied = true;
            $this->advance();

            Benchmark::measure('Applying Redis updates');
            $this->applyRedisUpdates();
            Benchmark::measure('Redis updates applied');
        }

        parent::rewind();
    }

    protected function applyRedisUpdates()
    {
        $type = null;
        $behaviors = null;

        $keys = [];
        $hostStateKeys = [];

        $showSourceGranted = $this->getAuth()->hasPermission('icingadb/object/show-source');

        $states = [];
        $hostStates = [];
        foreach ($this as $row) {
            if ($type === null) {
                $behaviors = $this->resolver->getBehaviors($row->state);

                switch (true) {
                    case $row instanceof Host:
                        $type = 'host';
                        break;
                    case $row instanceof Service:
                        $type = 'service';
                        break;
                    default:
                        throw new RuntimeException('Volatile states can only be fetched for hosts and services');
                }
            }

            $states[bin2hex($row->id)] = $row->state;
            if (empty($keys)) {
                $keys = $row->state->getColumns();
                if (! $showSourceGranted) {
                    $keys = array_diff($keys, ['check_commandline']);
                }
            }

            if ($type === 'service' && $row->host instanceof Host) {
                $hostStates[bin2hex($row->host->id)] = $row->host->state;
                if (empty($hostStateKeys)) {
                    $hostStateKeys = $row->host->state->getColumns();
                }
            }
        }

        if (empty($states)) {
            return;
        }

        foreach ($this->fetchStates("icinga:{$type}:state", array_keys($states), $keys) as $id => $data) {
            foreach ($data as $key => $value) {
                $data[$key] = $behaviors->retrieveProperty($value, $key);
            }

            $states[$id]->setProperties($data);
        }

        if ($type === 'service' && ! empty($hostStates)) {
            foreach ($this->fetchStates('icinga:host:state', array_keys($hostStates), $hostStateKeys) as $id => $data) {
                foreach ($data as $key => $value) {
                    $data[$key] = $behaviors->retrieveProperty($value, $key);
                }

                $hostStates[$id]->setProperties($data);
            }
        }
    }

    protected function fetchStates(string $key, array $ids, array $keys): Generator
    {
        $results = $this->redis->hmget($key, $ids);
        foreach ($results as $i => $json) {
            if ($json !== null) {
                $data = json_decode($json, true);
                $keyMap = array_fill_keys($keys, null);
                unset($keyMap['is_overdue']); // Is calculated by Icinga DB, not Icinga 2, hence it's never in redis

                // TODO: Remove once https://github.com/Icinga/icinga2/issues/9427 is fixed
                $data['state_type'] = $data['state_type'] === 0 ? 'soft' : 'hard';

                yield $ids[$i] => array_intersect_key(array_merge($keyMap, $data), $keyMap);
            }
        }
    }
}
