<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\BaseItemTable;
use Icinga\Module\Icingadb\Common\ViewMode;
use ipl\Web\Url;

class HostgroupTable extends BaseItemTable
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'hostgroup-table'];

    protected function init()
    {
        $this->setDetailUrl(Url::fromPath('icingadb/hostgroup'));
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
            ? HostgroupGridCell::class
            : HostgroupTableRow::class;
    }
}
