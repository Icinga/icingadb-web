<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Data;

use DateTime;
use DateTimeZone;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Orm\Model;
use ipl\Orm\Query;

trait CsvResultSetUtils
{
    /**
     * @return array<string, ?string>
     */
    public function current(): array
    {
        return $this->extractKeysAndValues(parent::current());
    }

    protected function formatValue(string $key, $value): ?string
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

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_string($value)) {
            return '"' . str_replace('"', '""', $value) . '"';
        } elseif (is_array($value)) {
            return '"' . implode(',', $value) . '"';
        } elseif ($value instanceof DateTime) {
            return $value->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s.vP');
        } else {
            return $value;
        }
    }

    protected function extractKeysAndValues(Model $model, string $path = ''): array
    {
        $keysAndValues = [];
        foreach ($model as $key => $value) {
            $keyPath = ($path ? $path . '.' : '') . $key;
            if ($value instanceof Model) {
                $keysAndValues += $this->extractKeysAndValues($value, $keyPath);
            } else {
                $keysAndValues[$keyPath] = $this->formatValue($key, $value);
            }
        }

        return $keysAndValues;
    }

    public static function stream(Query $query): void
    {
        if ($query->getModel() instanceof Host || $query->getModel() instanceof Service) {
            $query->setResultSetClass(VolatileCsvResults::class);
        } else {
            $query->setResultSetClass(__CLASS__);
        }

        if ($query->hasLimit()) {
            // Custom limits should still apply
            $query->peekAhead(false);
            $offset = $query->getOffset();
        } else {
            $query->limit(1000);
            $query->peekAhead();
            $offset = 0;
        }

        do {
            $query->offset($offset);
            $result = $query->execute()->disableCache();
            foreach ($result as $i => $keysAndValues) {
                if ($i === 0) {
                    echo implode(',', array_keys($keysAndValues));
                }

                echo "\r\n";

                echo implode(',', array_values($keysAndValues));

                JsonResultSet::giveMeMoreTime();
            }

            $offset += 1000;
        } while ($result->hasMore());

        exit;
    }
}
