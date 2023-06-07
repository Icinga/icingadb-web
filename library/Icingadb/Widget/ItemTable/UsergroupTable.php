<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\BaseItemTable;
use ipl\Web\Url;

class UsergroupTable extends BaseItemTable
{
    protected $defaultAttributes = ['class' => 'usergroup-table'];

    protected function init()
    {
        $this->setDetailUrl(Url::fromPath('icingadb/usergroup'));
    }

    protected function getItemClass(): string
    {
        return UsergroupTableRow::class;
    }
}
