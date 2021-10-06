<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\Links;
use ipl\Web\Url;

class ServiceList extends StateList
{
    protected $defaultAttributes = ['class' => 'service-list', 'data-state-interval' => 10];

    protected function getItemClass()
    {
        switch ($this->getViewMode()) {
            case 'minimal':
                return ServiceListItemMinimal::class;
            case 'detailed':
                return ServiceListItemDetailed::class;
            case 'objectHeader':
                return ServiceDetailHeader::class;
            default:
                return ServiceListItem::class;
        }
    }

    protected function init()
    {
        $this->setMultiselectUrl(Links::servicesDetails());
        $this->setDetailUrl(Url::fromPath('icingadb/service'));
    }
}
