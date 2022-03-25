<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Redis;

use Generator;
use Icinga\Application\Benchmark;
use Icinga\Module\Icingadb\Common\IcingaRedis;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Orm\Query;
use ipl\Orm\Resolver;
use ipl\Orm\ResultSet;
use RuntimeException;

class VolatileStateResults extends ResultSet
{
    /** @var Resolver */
    private $resolver;

    /** @var bool Whether Redis updates were applied */
    private $updatesApplied = false;

    public static function fromQuery(Query $query)
    {
        $self = parent::fromQuery($query);
        $self->resolver = $query->getResolver();

        return $self;
    }

    public function current()
    {
        if (! $this->updatesApplied && ! $this->isCacheDisabled) {
            $this->rewind();
        }

        return parent::current();
    }

    public function key(): int
    {
        if (! $this->updatesApplied && ! $this->isCacheDisabled) {
            $this->rewind();
        }

        return parent::key();
    }

    public function rewind(): void
    {
        if (! $this->updatesApplied && ! $this->isCacheDisabled) {
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
            if ($type === 'service' && $row->host instanceof Host) {
                $hostStates[bin2hex($row->host->id)] = $row->host->state;
            }
        }

        if (empty($states)) {
            return;
        }

        foreach ($this->fetchStates("icinga:{$type}:state", array_keys($states)) as $id => $data) {
            foreach ($data as $key => $value) {
                $data[$key] = $behaviors->retrieveProperty($value, $key);
            }

            $states[$id]->setProperties($data);
        }

        if ($type === 'service' && ! empty($hostStates)) {
            foreach ($this->fetchStates('icinga:host:state', array_keys($hostStates)) as $id => $data) {
                foreach ($data as $key => $value) {
                    $data[$key] = $behaviors->retrieveProperty($value, $key);
                }

                $hostStates[$id]->setProperties($data);
            }
        }
    }

    protected function fetchStates(string $key, array $ids): Generator
    {
        $results = IcingaRedis::instance()->getConnection()->hmget($key, $ids);
        foreach ($results as $i => $json) {
            if ($json !== null) {
                $data = json_decode($json, true);
                $data = array_intersect_key($data, array_flip(VolatileState::$keys));

                yield $ids[$i] => $data;
            }
        }
    }
}
