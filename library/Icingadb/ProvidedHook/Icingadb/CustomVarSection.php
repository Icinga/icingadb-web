<?php

/** Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use ipl\Html\BaseHtmlElement;

class CustomVarSection extends BaseHtmlElement
{
    // TODO: Class for custom HTML section
    protected $items;

    public function addItem(CustomVarItem $item)
    {
        $this->items[] = $item;
    }

    public function assemble()
    {
        $this->addHtml($this->items);
    }
}
