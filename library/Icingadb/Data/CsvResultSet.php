<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Data;

use ipl\Orm\ResultSet;

class CsvResultSet extends ResultSet
{
    use CsvResultSetUtils;
}
