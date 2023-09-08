<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\DetailActions;
use Icinga\Module\Icingadb\Common\ViewMode;
use ipl\Web\Common\BaseItemTable;
use ipl\Web\Url;

class ServicegroupTable extends BaseItemTable
{
    use DetailActions;
    use ViewMode;

    protected $defaultAttributes = ['class' => 'servicegroup-table'];

    protected function init(): void
    {
        $this->initializeDetailActions();
        $this->setDetailUrl(Url::fromPath('icingadb/servicegroup'));
    }

    protected function getLayout(): string
    {
        return $this->getViewMode() === 'grid'
            ? 'group-grid'
            : parent::getLayout();
    }

    protected function getItemClass(): string
    {
        return $this->getViewMode() === 'grid'
            ? ServicegroupGridCell::class
            : ServicegroupTableRow::class;
    }
}
