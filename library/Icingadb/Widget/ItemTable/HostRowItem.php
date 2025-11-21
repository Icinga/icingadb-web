<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Host;
use ipl\Html\BaseHtmlElement;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Link;

class HostRowItem extends StateRowItem
{
    /** @var HostItemTable */
    protected $list;

    /** @var Host */
    protected $item;

    protected function init()
    {
        parent::init();

        $this->list->addDetailFilterAttribute($this, Filter::equal('name', $this->item->name))
            ->addMultiselectFilterAttribute($this, Filter::equal('host.name', $this->item->name));
    }

    protected function assembleCell(BaseHtmlElement $cell, string $path, $value)
    {
        switch ($path) {
            case 'name':
            case 'display_name':
                $cell->addHtml(new Link($this->item->$path, Links::host($this->item), [
                    'class' => 'subject',
                    'title' => $this->item->$path
                ]));
                break;
            case 'service.name':
            case 'service.display_name':
                $column = substr($path, 8);
                $cell->addHtml(new Link(
                    $this->item->service->$column,
                    Links::service($this->item->service, $this->item)
                ));
                break;
            default:
                parent::assembleCell($cell, $path, $value);
        }
    }
}
