<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\DetailActions;
use ipl\Web\Common\BaseItemTable;
use ipl\Web\Url;

class UsergroupTable extends BaseItemTable
{
    use DetailActions;

    protected $defaultAttributes = ['class' => 'usergroup-table'];

    protected function init(): void
    {
        $this->initializeDetailActions();
        $this->setDetailUrl(Url::fromPath('icingadb/usergroup'));
    }

    protected function getItemClass(): string
    {
        return UsergroupTableRow::class;
    }
}
