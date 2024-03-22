<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Data;

use Icinga\Module\Icingadb\Redis\VolatileStateResults;

/**
 * @internal This class is supposed to be used by {@see CsvResultSet::stream()} only.
 */
final class VolatileCsvResults extends VolatileStateResults
{
    use CsvResultSetUtils;
}
