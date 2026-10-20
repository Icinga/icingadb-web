<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\ProvidedHook\Notifications\V2;

use Icinga\Module\Icingadb\ProvidedHook\Notifications\V1\Source;
use Icinga\Module\Notifications\Hook\V2\V1FilterMigrationHook;
use InvalidArgumentException;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\Parser;
use JsonException;

class V1FilterMigration implements V1FilterMigrationHook
{
    public function getSourceType(): string
    {
        return 'icinga2';
    }

    public function migrateFilter(string $v1Filter): Filter\Chain
    {
        try {
            $data = json_decode($v1Filter, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Invalid JSON: ' . $e->getMessage(), previous: $e);
        }

        if ($data['version'] !== 1 || ! isset($data['config']['type']) || ! isset($data['config']['filter'])) {
            throw new InvalidArgumentException('Invalid v1 filter format');
        }

        $typeFilter = match ($data['config']['type']) {
            Source::TYPE_ALL => null,
            'host' => Filter::equal('object.type', 'host'),
            'service' => Filter::equal('object.type', 'service'),
            default => throw new InvalidArgumentException('Unknown v1 filter type: ' . $data['config']['type'])
        };

        $filter = (new Parser($data['config']['filter']))->parse();

        if ($typeFilter !== null) {
            $filter = Filter::all($typeFilter, $filter);
        } elseif (! ($filter instanceof Filter\Chain)) {
            $filter = Filter::all($filter);
        }

        return $filter;
    }
}
