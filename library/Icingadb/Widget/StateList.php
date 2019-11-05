<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\ViewMode;

abstract class StateList extends BaseItemList
{
    use ViewMode;

    protected function assemble()
    {
        $this->addAttributes(['class' => $this->getViewMode()]);

        parent::assemble();
    }
}
