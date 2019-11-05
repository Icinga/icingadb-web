<?php

namespace Icinga\Module\Icingadb\Widget;

class ServiceList extends StateList
{
    protected $defaultAttributes = ['class' => 'service-list'];

    protected function getItemClass()
    {
        if ($this->getViewMode() === 'minimal') {
            return ServiceListItemMinimal::class;
        }

        return ServiceListItem::class;
    }
}
