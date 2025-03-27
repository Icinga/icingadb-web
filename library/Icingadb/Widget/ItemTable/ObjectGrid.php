<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Icingadb\Common\DetailActions;
use Icinga\Module\Icingadb\Model\Hostgroupsummary;
use Icinga\Module\Icingadb\Model\ServicegroupSummary;
use Icinga\Module\Icingadb\View\HostgroupGridRenderer;
use Icinga\Module\Icingadb\View\ServicegroupGridRenderer;
use ipl\Html\ValidHtml;
use ipl\Orm\Model;
use ipl\Stdlib\Filter;
use ipl\Web\Common\ItemRenderer;
use ipl\Web\Url;
use ipl\Web\Widget\ItemList;

/**
 * ObjectGrid
 *
 * @internal The only reason this class exists is due to the detail actions. In case those are part of the ipl
 * some time, this class is obsolete, and we must be able to safely drop it.
 */
class ObjectGrid extends ItemList
{
    use DetailActions;

    protected $defaultAttributes = ['class' => 'object-grid'];

    public function __construct($data, ItemRenderer $renderer)
    {
        parent::__construct($data, function (Model $item) use ($renderer) {
            if ($item instanceof Hostgroupsummary && $renderer instanceof HostgroupGridRenderer) {
                return $renderer;
            }

            if ($item instanceof ServicegroupSummary && $renderer instanceof ServicegroupGridRenderer) {
                return $renderer;
            }

            throw new NotImplementedError('Not implemented');
        });
    }

    protected function init(): void
    {
        parent::init();

        $this->initializeDetailActions();
    }

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
        }

        $this->addDetailFilterAttribute($item, Filter::equal('name', $data->name));

        return $item;
    }
}
