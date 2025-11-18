<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Redis;

use Icinga\Application\Benchmark;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Backend;
use Icinga\Module\Icingadb\Common\IcingaRedis;
use Icinga\Module\Icingadb\Model\DependencyNode;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\State;
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

    /** @var string Object type host */
    protected const TYPE_HOST = 'host';

    /** @var string Object type service */
    protected const TYPE_SERVICE = 'service';

    /** @var array|null Columns to be selected if they were explicitly set, if empty all columns are selected */
    protected ?array $columns;

    /** @var bool Whether the model's ID should be contained in the results */
    protected bool $includeModelID = true;

    public static function fromQuery(Query $query)
    {
        $self = parent::fromQuery($query);
        $self->resolver = $query->getResolver();
        $self->redisUnavailable = Backend::getRedis()->isUnavailable();
        $self->columns = $query->getColumns();

        if (! empty($self->columns)) {
            // The id is necessary to apply the redis-updates
            if ($query->getModel() instanceof Host && empty(array_intersect(['host.id', 'id'], $self->columns))) {
                $query->withColumns('host.id');
                $self->includeModelID = false;
            } elseif (
                $query->getModel() instanceof Service &&
                empty(array_intersect(['service.id', 'id'], $self->columns))
            ) {
                $query->withColumns('service.id');
                $self->includeModelID = false;
            }
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
        return $this->redisUnavailable;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        if (! $this->redisUnavailable && ! $this->updatesApplied && ! $this->isCacheDisabled) {
            $this->rewind();
        }

        $result = parent::current();
        if (! $this->includeModelID) {
            unset($result['id']);
        }

        return $result;
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
        $showSourceGranted = $this->getAuth()->hasPermission('icingadb/object/show-source');

        $getKeysAndBehaviors = function (State $state, $type): array {
            $columns = array_filter($state->getColumns(), function ($column) {
                return ! str_ends_with($column, '_id');
            });

            if (! empty($this->columns)) {
                $normalizedColumns = array_map(
                    fn($column) => preg_replace("/^($type\.state\.|state\.)/", '', $column),
                    $this->columns
                );

                $stateColumns = array_intersect($normalizedColumns, $columns);
                return [$stateColumns, $this->resolver->getBehaviors($state)];
            }

            return [$columns, $this->resolver->getBehaviors($state)];
        };

        $states = [];
        foreach ($rows as $row) {
            if ($row instanceof DependencyNode) {
                if ($row->redundancy_group_id !== null) {
                    continue;
                } elseif ($row->service_id !== null) {
                    $type = self::TYPE_SERVICE;
                    $row = $row->service;
                } else {
                    $type = self::TYPE_HOST;
                    $row = $row->host;
                }
            } elseif ($type === null) {
                switch (true) {
                    case $row instanceof Host:
                        $type = self::TYPE_HOST;
                        break;
                    case $row instanceof Service:
                        $type = self::TYPE_SERVICE;
                        break;
                    default:
                        throw new RuntimeException('Volatile states can only be fetched for hosts and services');
                }
            }

            $states[$type][bin2hex($row->id)] = $row->state;

            if (! isset($states[$type]['keys'])) {
                [$keys, $behaviors] = $getKeysAndBehaviors($row->state, $type);

                if (! $showSourceGranted) {
                    $keys = array_diff($keys, ['check_commandline']);
                }

                $states[$type]['keys'] = $keys;
                $states[$type]['behaviors'] = $behaviors;
            }

            if ($type === self::TYPE_SERVICE && $row->host instanceof Host && isset($row->host->id)) {
                $states[self::TYPE_HOST][bin2hex($row->host->id)] = $row->host->state;

                if (! isset($states[self::TYPE_HOST]['keys'])) {
                    [$keys, $behaviors] = $getKeysAndBehaviors($row->host->state, $type);

                    $states[self::TYPE_HOST]['keys'] = $keys;
                    $states[self::TYPE_HOST]['behaviors'] = $behaviors;
                }
            }
        }

        if (! empty($states[self::TYPE_SERVICE])) {
            $this->apply($states[self::TYPE_SERVICE], self::TYPE_SERVICE);
        }

        if (! empty($states[self::TYPE_HOST])) {
            $this->apply($states[self::TYPE_HOST], self::TYPE_HOST);
        }
    }

    /**
     * Apply the given states of given type to the results
     *
     * @param array $states
     * @param string $type The object type ({@see self::TYPE_HOST} OR {@see self::TYPE_SERVICE})
     *
     * @return void
     */
    protected function apply(array $states, string $type): void
    {
        $keys = $states['keys'];
        $behaviors = $states['behaviors'];

        unset($states['keys'], $states['behaviors']);

        $results = $type === self::TYPE_SERVICE
            ? IcingaRedis::fetchServiceState(array_keys($states), $keys)
            : IcingaRedis::fetchHostState(array_keys($states), $keys);

        foreach ($results as $id => $data) {
            foreach ($data as $key => $value) {
                $data[$key] = $behaviors->retrieveProperty($value, $key);
            }

            $states[$id]->setProperties($data);
        }
    }
}
