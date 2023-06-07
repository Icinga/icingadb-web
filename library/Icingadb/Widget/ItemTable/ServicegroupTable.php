<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\BaseItemTable;
use Icinga\Module\Icingadb\Common\ViewMode;
use ipl\Web\Url;

class ServicegroupTable extends BaseItemTable
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'servicegroup-table'];

    protected function init()
    {
        $this->setDetailUrl(Url::fromPath('icingadb/servicegroup'));
    }

    protected function getItemClass(): string
    {
        if ($this->getViewMode() === 'grid') {
            $this->addAttributes(['class' => 'group-grid']);
            return ServicegroupGridCell::class;
        }

        $this->addAttributes(['class' => 'table-layout']);
        return ServicegroupTableRow::class;
    }
}
