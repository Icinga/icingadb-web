<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Html\BaseHtmlElement;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Link;

class ServiceRowItem extends StateRowItem
{
    /** @var ServiceItemTable */
    protected $list;

    /** @var Service */
    protected $item;

    protected function init()
    {
        parent::init();

        $this->list->addMultiselectFilterAttribute(
            $this,
            Filter::all(
                Filter::equal('service.name', $this->item->name),
                Filter::equal('host.name', $this->item->host->name)
            )
        );
        $this->list->addDetailFilterAttribute(
            $this,
            Filter::all(
                Filter::equal('name', $this->item->name),
                Filter::equal('host.name', $this->item->host->name)
            )
        );
    }

    protected function assembleCell(BaseHtmlElement $cell, string $path, $value)
    {
        switch ($path) {
            case 'name':
            case 'display_name':
                $cell->addHtml(new Link(
                    $this->item->$path,
                    Links::service($this->item, $this->item->host),
                    [
                        'class' => 'subject',
                        'title' => $this->item->$path
                    ]
                ));
                break;
            case 'host.name':
            case 'host.display_name':
                $column = substr($path, 5);
                $cell->addHtml(new Link($this->item->host->$column, Links::host($this->item->host)));
                break;
            default:
                parent::assembleCell($cell, $path, $value);
        }
    }
}
