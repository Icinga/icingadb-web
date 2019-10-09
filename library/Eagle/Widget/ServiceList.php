<?php

namespace Icinga\Module\Eagle\Widget;

class ServiceList extends StateList
{
    protected $defaultAttributes = ['class' => 'service-list'];

    protected function getItemClass()
    {
        if ($this->getViewMode() === 'compact') {
            return ServiceListItemCompact::class;
        }

        return ServiceListItem::class;
    }
}
