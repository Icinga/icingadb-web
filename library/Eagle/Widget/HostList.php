<?php

namespace Icinga\Module\Eagle\Widget;

/**
 * Host list
 */
class HostList extends StateList
{
    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'object-list', 'data-base-target' => '_next'];

    protected function getItemClass()
    {
        if ($this->getViewMode() === 'compact') {
            return HostListItemCompact::class;
        }

        return HostListItem::class;
    }
}
