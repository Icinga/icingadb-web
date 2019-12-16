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
        if ($this->getViewMode() === 'minimal') {
            return HostListItemMinimal::class;
        }

        return HostListItem::class;
    }

    protected function init()
    {
        $this->setMultiselectUrl(Links::hostsDetails());
    }
}
