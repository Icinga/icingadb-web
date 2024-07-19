<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Data;

use DateTime;
use DateTimeZone;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Util\Json;
use ipl\Orm\Model;
use ipl\Orm\Query;

trait JsonResultSetUtils
{
    /**
     * @return array<string, ?string>
     */
    public function current(): array
    {
        return $this->createObject(parent::current());
    }

    protected function formatValue(string $key, $value)
    {
        if (
            $value
            && (
                $key === 'id'
                || substr($key, -3) === '_id'
                || substr($key, -3) === '.id'
                || substr($key, -9) === '_checksum'
                || substr($key, -4) === '_bin'
            )
        ) {
            $value = bin2hex($value);
        }

        if ($value instanceof DateTime) {
            return $value->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s.vP');
        }

        return $value;
    }

    protected function createObject(Model $model): array
    {
        $keysAndValues = [];
        foreach ($model as $key => $value) {
            if ($value instanceof Model) {
                $keysAndValues[$key] = $this->createObject($value);
            } else {
                $keysAndValues[$key] = $this->formatValue($key, $value);
            }
        }

        return $keysAndValues;
    }

    public static function stream(Query $query): void
    {
        if ($query->getModel() instanceof Host || $query->getModel() instanceof Service) {
            $query->setResultSetClass(VolatileJsonResults::class);
        } else {
            $query->setResultSetClass(__CLASS__);
        }

        if ($query->hasLimit()) {
            // Custom limits should still apply
            $query->peekAhead(false);
            $offset = $query->getOffset() ?? 0;
        } else {
            $query->limit(1000);
            $query->peekAhead();
            $offset = 0;
        }

        echo '[';

        do {
            $query->offset($offset);
            $result = $query->execute()->disableCache();
            foreach ($result as $i => $object) {
                if ($i > 0 || $offset !== 0) {
                    echo ",\n";
                }

                echo Json::sanitize($object);

                self::giveMeMoreTime();
            }

            $offset += 1000;
        } while ($result->hasMore());

        echo ']';

        exit;
    }

    /**
     * Grant the caller more time to work with
     *
     * This resets the execution time before it runs out. The advantage of this, compared with no execution time
     * limit at all, is that only the caller can bypass the limit. Any other (faulty) code will still be stopped.
     *
     * @internal Don't use outside of {@see JsonResultSet::stream()} or {@see CsvResultSet::stream()}
     *
     * @return void
     */
    public static function giveMeMoreTime()
    {
        $spent = getrusage();
        if ($spent !== false) {
            $maxExecutionTime = ini_get('max_execution_time');
            if (! $maxExecutionTime || ! is_numeric($maxExecutionTime)) {
                $maxExecutionTime = 30;
            } else {
                $maxExecutionTime = (int) $maxExecutionTime;
            }

            if ($maxExecutionTime > 0) {
                $timeRemaining = $maxExecutionTime - $spent['ru_utime.tv_sec'] % $maxExecutionTime;
                if ($timeRemaining <= 5) {
                    set_time_limit($maxExecutionTime);
                }
            }
        }
    }
}
