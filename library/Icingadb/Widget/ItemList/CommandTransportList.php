<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\DetailActions;
use ipl\Web\Common\BaseOrderedItemList;
use ipl\Web\Url;

class CommandTransportList extends BaseOrderedItemList
{
    use DetailActions;

    protected function init(): void
    {
        $this->getAttributes()->add('class', 'command-transport-list');
        $this->initializeDetailActions();
        $this->setDetailUrl(Url::fromPath('icingadb/command-transport/show'));
    }

    protected function getItemClass(): string
    {
        return CommandTransportListItem::class;
    }
}
