<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Data;

use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Orm\ResultSet;

class CsvResultSet extends ResultSet
{
    protected $isCacheDisabled = true;

    public function current()
    {
        return $this->extractKeysAndValues(parent::current());
    }

    protected function formatValue(string $key, ?string $value): ?string
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
        $query->setResultSetClass(__CLASS__);

        foreach ($query as $i => $keysAndValues) {
            if ($i === 0) {
                echo implode(',', array_keys($keysAndValues));
            }

            echo "\r\n";

            echo implode(',', array_values($keysAndValues));
        }

        exit;
    }
}
