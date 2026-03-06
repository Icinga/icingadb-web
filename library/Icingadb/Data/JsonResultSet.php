<?php

// SPDX-FileCopyrightText: 2022 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Data;

use ipl\Orm\ResultSet;

class JsonResultSet extends ResultSet
{
    use JsonResultSetUtils;
}
