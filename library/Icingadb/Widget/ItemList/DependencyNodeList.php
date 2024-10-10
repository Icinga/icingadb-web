<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Model\DependencyNode;
use Icinga\Module\Icingadb\Model\UnreachableParent;
use ipl\Web\Common\BaseListItem;

/**
 * Dependency node list
 */
class DependencyNodeList extends StateList
{
    protected $defaultAttributes = ['class' => ['dependency-node-list']];

    protected function init(): void
    {
        $this->initializeDetailActions();
    }

    protected function getItemClass(): string
    {
        return '';
    }

    protected function createListItem(object $data): BaseListItem
    {
        /** @var UnreachableParent|DependencyNode $data */
        if ($data->redundancy_group_id !== null) {
            return new RedundancyGroupListItem($data->redundancy_group, $this);
        } elseif ($data->service_id !== null) {
            return new ServiceListItem($data->service, $this);
        } else {
            return new HostListItem($data->host, $this);
        }
    }
}
