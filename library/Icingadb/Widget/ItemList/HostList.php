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
                $this->removeAttribute('class', 'default-layout');

                return HostListItemDetailed::class;
            default:
                return HostListItem::class;
        }
    }

    protected function init(): void
    {
        $this->initializeDetailActions();
        $this->setMultiselectUrl(Links::hostsDetails());
        $this->setDetailUrl(Url::fromPath('icingadb/host'));
    }
}
