<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Widget\ItemList;

use DateTimeInterface;
use Icinga\Module\Icingadb\View\AlertRenderer;
use ipl\Orm\Query;
use ipl\Orm\ResultSet;
use ipl\Web\Widget\ItemList;

class AlertList extends ObjectList
{
    /**
     * Create a list of alerts
     *
     * @param ResultSet|Query $data
     * @param bool $useRelativeTimestamps Whether to use relative timestamps
     * @param ?DateTimeInterface $referenceTime Absolute timestamps on the same day as this omit the date
     */
    public function __construct(
        ResultSet|Query $data,
        bool $useRelativeTimestamps = true,
        ?DateTimeInterface $referenceTime = null
    ) {
        ItemList::__construct($data, new AlertRenderer($useRelativeTimestamps, $referenceTime));
    }
}
