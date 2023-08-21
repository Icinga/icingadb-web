<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Data;

use DateTime;
use DateTimeZone;
use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use Icinga\Util\Json;
use ipl\Orm\Model;
use ipl\Orm\Query;

class JsonResultSet extends VolatileStateResults
{
    protected $isCacheDisabled = true;

    /**
     * @return array<string, ?string>
     */
    public function current(): array
    {
        return $this->createObject(parent::current());
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
        $query->setResultSetClass(__CLASS__);

        echo '[';
        foreach ($query as $i => $object) {
            if ($i > 0) {
                echo ",\n";
            }

            echo Json::sanitize($object);
        }

        echo ']';

        exit;
    }
}
