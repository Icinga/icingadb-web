<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\Links;
use ipl\Web\Url;

/**
 * Host list
 */
class HostList extends StateList
{
    protected $defaultAttributes = ['class' => 'host-list'];

    protected function getItemClass(): string
    {
        switch ($this->getViewMode()) {
            case 'minimal':
                return HostListItemMinimal::class;
            case 'detailed':
                return HostListItemDetailed::class;
            case 'objectHeader':
                return HostDetailHeader::class;
            default:
                return HostListItem::class;
        }
    }

    protected function init()
    {
        $this->setMultiselectUrl(Links::hostsDetails());
        $this->setDetailUrl(Url::fromPath('icingadb/host'));
    }
}
