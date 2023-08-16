<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\BaseItemTable;
use Icinga\Module\Icingadb\Common\ViewMode;
use ipl\Web\Url;

class TacticallineTable extends BaseItemTable
{
    use ViewMode;

    protected $defaultAttributes = ['class' => 'hostgroup-table'];

    protected function init()
    {
        $this->setDetailUrl(Url::fromPath('icingadb/tacticalline'));
    }

    protected function getItemClass(): string
    {
        $this->addAttributes(['class' => 'table-layout']);
        return TacticallineTableRow::class;
    }
}
