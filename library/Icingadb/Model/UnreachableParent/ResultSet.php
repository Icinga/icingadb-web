<?php

// SPDX-FileCopyrightText: 2025 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Model\UnreachableParent;

use Generator;
use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use Traversable;

class ResultSet extends VolatileStateResults
{
    protected function yieldTraversable(Traversable $traversable): Generator
    {
        $knownIds = [];
        foreach ($traversable as $value) {
            if (isset($knownIds[$value->id])) {
                continue;
            }

            $knownIds[$value->id] = true;

            yield $value;
        }
    }
}
