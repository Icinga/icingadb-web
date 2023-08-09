<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\DetailActions;
use ipl\Web\Common\BaseItemTable;
use ipl\Web\Url;

class UserTable extends BaseItemTable
{
    use DetailActions;

    protected $defaultAttributes = ['class' => 'user-table'];

    protected function init(): void
    {
        $this->initializeDetailActions();
        $this->setDetailUrl(Url::fromPath('icingadb/user'));
    }

    protected function getItemClass(): string
    {
        return UserTableRow::class;
    }
}
