<?php

namespace Icinga\Module\Eagle\Widget;

use Icinga\Module\Eagle\Common\ViewMode;

abstract class StateList extends BaseItemList
{
    use ViewMode;

    protected function assemble()
    {
        $this->addAttributes(['class' => $this->getViewMode()]);

        parent::assemble();
    }
}
