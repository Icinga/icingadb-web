<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\DetailActions;
use Icinga\Module\Icingadb\Common\Links;
use ipl\Web\Url;

class HostItemTable extends StateItemTable
{
    use DetailActions;

    protected function init()
    {
        $this->initializeDetailActions();
        $this->setMultiselectUrl(Links::hostsDetails());
        $this->setDetailUrl(Url::fromPath('icingadb/host'));
    }

    protected function getItemClass(): string
    {
        return HostRowItem::class;
    }

    protected function getVisualColumn(): string
    {
        return 'host.state.severity';
    }
}
