<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\BaseItemList;
use Icinga\Module\Icingadb\Common\CaptionDisabled;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Common\ObjectLinkDisabled;
use Icinga\Module\Icingadb\Common\ViewMode;
use ipl\Web\Url;

class DowntimeList extends BaseItemList
{
    use CaptionDisabled;
    use NoSubjectLink;
    use ObjectLinkDisabled;
    use ViewMode;

    protected $defaultAttributes = ['class' => 'downtime-list'];

    protected function getItemClass(): string
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
