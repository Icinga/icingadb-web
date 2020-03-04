<?php

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Data\Filter\Filter;
use Icinga\Module\Icingadb\Common\BaseTableRowItem;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Widget\Detail\HostStatistics;
use Icinga\Module\Icingadb\Widget\Detail\ServiceStatistics;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Web\Widget\Link;

/** @property HostgroupList $list */
class HostgroupListItem extends BaseTableRowItem
{
    protected function assembleColumns(HtmlDocument $columns)
    {
        $hostStats = new HostStatistics($this->item);

        $hostStats->setBaseFilter(Filter::where('hostgroup.name', $this->item->name));
        if ($this->list->hasBaseFilter()) {
            $hostStats->setBaseFilter(
                $hostStats->getBaseFilter()->andFilter($this->list->getBaseFilter())
            );
        }

        $columns->addFrom($hostStats, function (BaseHtmlElement $item) {
            $item->getAttributes()->add(['class' => 'col']);
            $item->setTag('div');
            return $item;
        });

        $serviceStats = new ServiceStatistics($this->item);

        $serviceStats->setBaseFilter(Filter::where('hostgroup.name', $this->item->name));
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
            new Link($this->item->display_name, Links::hostgroup($this->item)),
            Html::tag('br'),
            $this->item->name
        ]);
    }
}
