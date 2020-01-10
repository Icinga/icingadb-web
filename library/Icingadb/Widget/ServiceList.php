<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\Links;

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

    protected function init()
    {
        $this->setMultiselectUrl(Links::servicesDetails());
    }
}
