<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Common\ListItemCommonLayout;
use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Model\RedundancyGroupSummary;
use Icinga\Module\Icingadb\Model\RedundancyGroupState;
use Icinga\Module\Icingadb\Widget\DependencyNodeStatistics;
use ipl\Html\BaseHtmlElement;
use ipl\Stdlib\Filter;
use ipl\Web\Url;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\TimeSince;

/**
 * Redundancy group list item. Represents one database row.
 *
 * @property RedundancyGroup $item
 * @property RedundancyGroupState $state
 */
class RedundancyGroupListItem extends StateListItem
{
    use ListItemCommonLayout;
    use Database;
    use Auth;

    protected $defaultAttributes = ['class' => ['redundancy-group-list-item']];

    protected function init(): void
    {
        parent::init();

        $this->addAttributes(['data-action-item' => true]);
    }

    protected function getStateBallSize(): string
    {
        return StateBall::SIZE_LARGE;
    }

    protected function createTimestamp(): BaseHtmlElement
    {
        return new TimeSince($this->state->last_state_change->getTimestamp());
    }

    protected function createSubject(): Link
    {
        return new Link(
            $this->item->display_name,
            Url::fromPath('icingadb/redundancygroup', ['id' => bin2hex($this->item->id)]),
            ['class' => 'subject']
        );
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $stateBall = new StateBall($this->state->getStateText(), $this->getStateBallSize());
        $stateBall->add($this->state->getIcon());

        $visual->addHtml($stateBall);
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $summary = RedundancyGroupSummary::on($this->getDb())
            ->filter(Filter::equal('id', $this->item->id));

        $this->applyRestrictions($summary);

        $caption->addHtml(new DependencyNodeStatistics($summary->first()));
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml($this->createSubject());
        if ($this->state->failed) {
            $text = $this->translate('has no working objects');
        } else {
            $text = $this->translate('has working objects');
        }

        $title->addHtml(HtmlElement::create('span', null, Text::create($text)));
    }

    protected function assemble(): void
    {
        $this->add([
            $this->createVisual(),
            $this->createIconImage(),
            $this->createMain()
        ]);
    }
}
