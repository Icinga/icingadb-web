<?php

namespace Icinga\Module\Icingadb\Widget;

/**
 * Host list
 */
class HostList extends StateList
{
    protected $defaultAttributes = ['class' => 'host-list'];

    protected function getItemClass()
    {
        if ($this->getViewMode() === 'minimal') {
            return HostListItemMinimal::class;
        }

        return HostListItem::class;
    }
}
