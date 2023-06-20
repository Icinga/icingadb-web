<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use ipl\Html\BaseHtmlElement;
use ipl\Web\Widget\Link;

trait GridCellLayout
{
    /**
     * Creates a state badge for the Host / Service group with the highest severity that an object in the group has,
     * along with the count of the objects with this severity belonging to the corresponding group.
     *
     * @return Link
     */
    abstract public function createGroupBadge(): Link;

    protected function assembleVisual(BaseHtmlElement $visual)
    {
        $visual->add($this->createGroupBadge());
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
        $title->addHtml(
            $this->createSubject(),
            $this->createCaption()
        );
    }

    protected function assemble()
    {
        $this->add([
            $this->createTitle()
        ]);
    }
}
