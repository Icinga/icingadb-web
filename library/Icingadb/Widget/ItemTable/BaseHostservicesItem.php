<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Servicegroup;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Common\BaseTableRowItem;
use ipl\Web\Widget\Link;

abstract class BaseHostservicesItem extends BaseTableRowItem
{
    use Translation;

    protected function init(): void
    {
        if (isset($this->table)) {
            $this->table->addDetailFilterAttribute($this, Filter::equal('name', $this->item->name));
        }
    }

    protected function createSubject(): BaseHtmlElement
    {
        return isset($this->table)
            ? new Link(
                $this->item->display_name,
                Links::hostDetails($this->item),
                [
                    'class' => 'subject',
                    'title' => sprintf(
                        $this->translate('List all Host "%s"'),
                        $this->item->display_name
                    )
                ]
            )
            : new HtmlElement(
                'span',
                Attributes::create(['class' => 'subject']),
                Text::create($this->item->display_name)
            );
    }

    protected function createCaption(): BaseHtmlElement
    {
        return new HtmlElement('span', null, Text::create($this->item->name));
    }
}
