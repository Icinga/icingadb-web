<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Data\Filter\Filter;
use Icinga\Module\Icingadb\Common\BaseTableRowItem;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Widget\Detail\ServiceStatistics;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Web\Widget\Link;

/** @property ServicegroupList $list */
class ServicegroupListItem extends BaseTableRowItem
{
    protected function assembleColumns(HtmlDocument $columns)
    {
        $serviceStats = new ServiceStatistics($this->item);

        $serviceStats->setBaseFilter(Filter::where('servicegroup.name', $this->item->name));
        if ($this->list->hasBaseFilter()) {
            $serviceStats->setBaseFilter(
                $serviceStats->getBaseFilter()->andFilter($this->list->getBaseFilter())
            );
        }

        $columns->addFrom($serviceStats, function (BaseHtmlElement $item) {
            $item->getAttributes()->add(['class' => 'col']);
            $item->setTag('div');
            return $item;
        });
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
        $title->add([
            new Link($this->item->display_name, Links::servicegroup($this->item)),
            Html::tag('br'),
            $this->item->name
        ]);
    }
}
