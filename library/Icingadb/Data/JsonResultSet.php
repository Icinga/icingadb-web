<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Data;

use Icinga\Module\Icingadb\Redis\VolatileStateResults;

class JsonResultSet extends VolatileStateResults
{
    use JsonResultSetUtils;
}
