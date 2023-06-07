<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\BaseItemTable;
use ipl\Web\Url;

class UserTable extends BaseItemTable
{
    protected $defaultAttributes = ['class' => 'user-table'];

    protected function init()
    {
        $this->setDetailUrl(Url::fromPath('icingadb/user'));
    }

    protected function getItemClass(): string
    {
        return UserTableRow::class;
    }
}
