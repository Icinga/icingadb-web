<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\CaptionDisabled;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\ViewMode;
use Icinga\Module\Icingadb\Widget\ItemList\DowntimeListItemMinimal;
use ipl\Web\Url;

class DowntimeList extends BaseItemList
{
    use CaptionDisabled;
    use ViewMode;

    protected $defaultAttributes = ['class' => 'downtime-list'];

    protected function getItemClass()
    {
        $viewMode = $this->getViewMode();

        $this->addAttributes(['class' => $viewMode]);

        if ($viewMode === 'minimal') {
            return DowntimeListItemMinimal::class;
        }

        return DowntimeListItem::class;
    }

    protected function init()
    {
        $this->setMultiselectUrl(Links::downtimesDetails());
        $this->setDetailUrl(Url::fromPath('icingadb/downtime'));
    }
}
