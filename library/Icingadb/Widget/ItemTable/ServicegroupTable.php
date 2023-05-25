<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\BaseItemTable;
use ipl\Web\Url;

class ServicegroupTable extends BaseItemTable
{
    protected $defaultAttributes = ['class' => 'servicegroup-table'];

    protected function init()
    {
        $this->setDetailUrl(Url::fromPath('icingadb/servicegroup'));
    }

    protected function getItemClass(): string
    {
        return ServicegroupTableRow::class;
    }
}
