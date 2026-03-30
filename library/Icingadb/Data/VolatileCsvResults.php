<?php

// SPDX-FileCopyrightText: 2024 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Data;

use Icinga\Module\Icingadb\Redis\VolatileStateResults;

/**
 * @internal This class is supposed to be used by {@see CsvResultSet::stream()} only.
 */
final class VolatileCsvResults extends VolatileStateResults
{
    use CsvResultSetUtils;
}
