<?php

namespace Icinga\Module\Eagle\Widget;

use Icinga\Module\Eagle\Common\ViewMode;

/**
 * Host list
 */
class HostList extends StateList
{
    use ViewMode;

    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'object-list', 'data-base-target' => '_next'];

    protected function getItemClass()
    {
        $viewMode = $this->getViewMode();

        $this->addAttributes(['class' => $viewMode]);

        if ($viewMode === 'compact') {
            return HostListItemCompact::class;
        }

        return HostListItem::class;
    }
}
