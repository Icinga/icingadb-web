<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;

trait TableRowLayout
{
    protected function assembleColumns(HtmlDocument $columns)
    {
        foreach ($this->createStatistics() as $objectStatistic) {
            $columns->addHtml($objectStatistic);
        }
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
        $title->addHtml(
            $this->createSubject(),
            $this->createCaption()
        );
    }
}
