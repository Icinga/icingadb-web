<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Redis;

use Icinga\Application\Benchmark;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\IcingaRedis;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Orm\Query;
use ipl\Orm\Resolver;
use ipl\Orm\ResultSet;
use RuntimeException;

class VolatileStateResults extends ResultSet
{
    use Auth;

    /** @var Resolver */
    private $resolver;

    /** @var bool Whether Redis is unavailable */
    private $redisUnavailable;

    /** @var bool Whether Redis updates were applied */
    private $updatesApplied = false;

    public static function fromQuery(Query $query)
    {
        $self = parent::fromQuery($query);
        $self->resolver = $query->getResolver();
        $self->redisUnavailable = IcingaRedis::isUnavailable();

        return $self;
    }

    /**
     * Get whether Redis is unavailable
     *
     * @return bool
     */
    public function isRedisUnavailable(): bool
    {
        return $this->redisUnavailable;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        if (! $this->redisUnavailable && ! $this->updatesApplied && ! $this->isCacheDisabled) {
            $this->rewind();
        }

        return parent::current();
    }

    public function next(): void
    {
        parent::next();

        if (! $this->redisUnavailable && $this->isCacheDisabled && $this->valid()) {
            $this->applyRedisUpdates([parent::current()]);
        }
    }

    public function key(): int
    {
        if (! $this->redisUnavailable && ! $this->updatesApplied && ! $this->isCacheDisabled) {
            $this->rewind();
        }

        return parent::key();
    }

    public function rewind(): void
    {
        if (! $this->redisUnavailable && ! $this->updatesApplied && ! $this->isCacheDisabled) {
            $this->updatesApplied = true;
            $this->advance();

            Benchmark::measure('Applying Redis updates');
            $this->applyRedisUpdates($this);
            Benchmark::measure('Redis updates applied');
        }

        parent::rewind();
    }

    /**
     * Apply redis state details to the given results
     *
     * @param self|array<int, mixed> $rows
     *
     * @return void
     */
    protected function applyRedisUpdates($rows)
    {
        $type = null;
        $behaviors = null;

        $keys = [];
        $hostStateKeys = [];

        $showSourceGranted = $this->getAuth()->hasPermission('icingadb/object/show-source');

        $states = [];
        $hostStates = [];
        foreach ($rows as $row) {
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

        if ($type === 'service') {
            $results = IcingaRedis::fetchServiceState(array_keys($states), $keys);
        } else {
            $results = IcingaRedis::fetchHostState(array_keys($states), $keys);
        }

        foreach ($results as $id => $data) {
            foreach ($data as $key => $value) {
                $data[$key] = $behaviors->retrieveProperty($value, $key);
            }

            $states[$id]->setProperties($data);
        }

        if ($type === 'service' && ! empty($hostStates)) {
            foreach (IcingaRedis::fetchHostState(array_keys($hostStates), $hostStateKeys) as $id => $data) {
                foreach ($data as $key => $value) {
                    $data[$key] = $behaviors->retrieveProperty($value, $key);
                }

                $hostStates[$id]->setProperties($data);
            }
        }
    }
}
