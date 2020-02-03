<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\Links;

class ServiceList extends StateList
{
    protected $defaultAttributes = ['class' => 'service-list'];

    protected function getItemClass()
    {
        switch ($this->getViewMode()) {
            case 'minimal':
                return ServiceListItemMinimal::class;
            case 'detailed':
                return ServiceListItemDetailed::class;
            default:
                return ServiceListItem::class;
        }
    }

    protected function init()
    {
        $this->setMultiselectUrl(Links::servicesDetails());
    }
}
