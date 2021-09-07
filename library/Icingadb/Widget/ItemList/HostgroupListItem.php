<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\BaseTableRowItem;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Model\Hostgroup;
use Icinga\Module\Icingadb\Widget\Detail\HostStatistics;
use Icinga\Module\Icingadb\Widget\Detail\ServiceStatistics;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Link;

/**
 * Hostgroup item of a hostgroup list. Represents one database row.
 *
 * @property Hostgroup $item
 * @property HostgroupList $list
 */
class HostgroupListItem extends BaseTableRowItem
{
    use NoSubjectLink;

    protected function init()
    {
        $this->setNoSubjectLink($this->list->getNoSubjectLink());
        $this->list->addDetailFilterAttribute($this, Filter::equal('name', $this->item->name));
    }

    protected function assembleColumns(HtmlDocument $columns)
    {
        $hostStats = new HostStatistics($this->item);

        $hostStats->setBaseFilter(Filter::equal('hostgroup.name', $this->item->name));
        if ($this->list->hasBaseFilter()) {
            $hostStats->setBaseFilter(
                Filter::all($hostStats->getBaseFilter(), $this->list->getBaseFilter())
            );
        }

        $columns->addFrom($hostStats, function (BaseHtmlElement $item) {
            $item->getAttributes()->add(['class' => 'col']);
            $item->setTag('div');
            return $item;
        });

        $serviceStats = new ServiceStatistics($this->item);

        $serviceStats->setBaseFilter(Filter::equal('hostgroup.name', $this->item->name));
        if ($this->list->hasBaseFilter()) {
            $serviceStats->setBaseFilter(
                Filter::all($serviceStats->getBaseFilter(), $this->list->getBaseFilter())
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
        $title->addHtml(
            $this->getNoSubjectLink()
                ? new HtmlElement(
                    'span',
                    Attributes::create(['class' => 'subject']),
                    Text::create($this->item->display_name)
                )
                : new Link($this->item->display_name, Links::hostgroup($this->item), ['class' => 'subject']),
            new HtmlElement('br'),
            Text::create($this->item->name)
        );
    }
}
