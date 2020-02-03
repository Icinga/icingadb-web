<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\Links;

/**
 * Host list
 */
class HostList extends StateList
{
    protected $defaultAttributes = ['class' => 'host-list'];

    protected function getItemClass()
    {
        switch ($this->getViewMode()) {
            case 'minimal':
                return HostListItemMinimal::class;
            case 'detailed':
                return HostListItemDetailed::class;
            default:
                return HostListItem::class;
        }
    }

    protected function init()
    {
        $this->setMultiselectUrl(Links::hostsDetails());
    }
}
