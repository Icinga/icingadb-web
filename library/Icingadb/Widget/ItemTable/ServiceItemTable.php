<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\DetailActions;
use Icinga\Module\Icingadb\Common\Links;
use ipl\Web\Url;

class ServiceItemTable extends StateItemTable
{
    use DetailActions;

    protected function init()
    {
        $this->initializeDetailActions();
        $this->setMultiselectUrl(Links::servicesDetails());
        $this->setDetailUrl(Url::fromPath('icingadb/service'));
    }

    protected function getItemClass(): string
    {
        return ServiceRowItem::class;
    }

    protected function getVisualColumn(): string
    {
        return 'service.state.severity';
    }
}
