<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Common\ListItemCommonLayout;
use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Model\RedundancyGroupState;
use Icinga\Module\Icingadb\Model\RedundancyGroupSummary;
use Icinga\Module\Icingadb\Widget\Detail\RedundancyGroupHeaderUtils;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Filter;
use ipl\Web\Common\BaseListItem;
use ipl\Web\Url;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;

/**
 * Redundancy group list item. Represents one database row.
 *
 * @property RedundancyGroup $item
 * @property RedundancyGroupState $state
 */
class RedundancyGroupListItem extends BaseListItem
{
    use ListItemCommonLayout;
    use Database;
    use Auth;
    use RedundancyGroupHeaderUtils;

    protected $defaultAttributes = ['class' => ['redundancy-group-list-item']];

    protected function init(): void
    {
        $this->addAttributes(['data-action-item' => true]);
    }

    protected function getObject(): RedundancyGroup
    {
        return $this->item;
    }

    protected function getSummary(): RedundancyGroupSummary
    {
        $summary = RedundancyGroupSummary::on($this->getDb())
            ->filter(Filter::equal('id', $this->item->id));

        $this->applyRestrictions($summary);

        return $summary->first();
    }

    protected function createSubject(): ValidHtml
    {
        return new Link(
            $this->item->display_name,
            Url::fromPath('icingadb/redundancygroup', ['id' => bin2hex($this->item->id)]),
            ['class' => 'subject']
        );
    }

    protected function getStateBallSize(): string
    {
        return StateBall::SIZE_LARGE;
    }

    protected function assemble(): void
    {
        $this->add([
            $this->createVisual(),
            $this->createMain()
        ]);
    }
}
