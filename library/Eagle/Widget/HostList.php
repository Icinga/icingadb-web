<?php

namespace Icinga\Module\Eagle\Widget;

/**
 * Host list
 */
class HostList extends StateList
{
    protected $defaultAttributes = ['class' => 'host-list'];

    protected function getItemClass()
    {
        if ($this->getViewMode() === 'compact') {
            return HostListItemCompact::class;
        }

        return HostListItem::class;
    }
}
