<?php

/** Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use ipl\Html\BaseHtmlElement;
use ipl\Html\ValidHtml;

class CustomVarItem extends BaseHtmlElement
{
    // TODO: Class for an item in custom HTML Section
    protected $item;

    public function __construct(ValidHtml $item)
    {
        $this->item = $item;
    }

    protected function assemble()
    {
        $this->addHtml($this->item);
    }
}
