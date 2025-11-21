<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Icingadb\Common\DetailActions;
use Icinga\Module\Icingadb\Model\Hostgroupsummary;
use Icinga\Module\Icingadb\Model\ServicegroupSummary;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Filter;
use ipl\Web\Url;
use ipl\Web\Widget\ItemTable;

/**
 * ObjectTable
 *
 * @internal The only reason this class exists is due to the detail actions. In case those are part of the ipl
 * some time, this class is obsolete, and we must be able to safely drop it.
 *
 * @template Item of Hostgroupsummary|ServicegroupSummary
 *
 * @extends ItemTable<Item>
 */
class ObjectTable extends ItemTable
{
    use DetailActions;

    protected function init(): void
    {
        parent::init();

        $this->initializeDetailActions();
    }

    /**
     * @param Item $data
     *
     * @return ValidHtml
     *
     * @throws NotImplementedError When the data is not of the expected type
     */
    protected function createListItem(object $data): ValidHtml
    {
        $item = parent::createListItem($data);

        if ($this->getDetailActionsDisabled()) {
            return $item;
        }

        switch (true) {
            case $data instanceof Hostgroupsummary:
                $this->setDetailUrl(Url::fromPath('icingadb/hostgroup'));

                break;
            case $data instanceof ServicegroupSummary:
                $this->setDetailUrl(Url::fromPath('icingadb/servicegroup'));

                break;
            default:
                throw new NotImplementedError('Not implemented');
        }

        $this->addDetailFilterAttribute($item, Filter::equal('name', $data->name));

        return $item;
    }
}
