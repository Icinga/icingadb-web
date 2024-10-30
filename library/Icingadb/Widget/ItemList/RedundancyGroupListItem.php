<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Common\ListItemCommonLayout;
use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Model\RedundancyGroupSummary;
use Icinga\Module\Icingadb\Model\RedundancyGroupState;
use Icinga\Module\Icingadb\Widget\DependencyNodeStatistics;
use ipl\Html\BaseHtmlElement;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\StateBall;
use ipl\Html\HtmlElement;
use ipl\Html\Attributes;
use ipl\Html\Text;
use ipl\Web\Widget\TimeSince;

/**
 * Redundancy group list item. Represents one database row.
 *
 * @property RedundancyGroup $item
 */
class RedundancyGroupListItem extends StateListItem
{
    use ListItemCommonLayout;
    use Database;
    use Translation;

    protected $defaultAttributes = ['class' => ['redundancy-group-list-item']];

    /** @var RedundancyGroupState */
    protected $state;

    protected function getStateBallSize(): string
    {
        return StateBall::SIZE_LARGE;
    }

    protected function createTimestamp(): BaseHtmlElement
    {
        return new TimeSince($this->state->last_state_change->getTimestamp());
    }

    protected function createSubject(): BaseHtmlElement
    {
        return new HtmlElement(
            'span',
            Attributes::create(['class' => 'subject']),
            Text::create($this->item->display_name)
        );
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new StateBall($this->state->getStateText(), $this->getStateBallSize()));
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $caption->addHtml(new DependencyNodeStatistics(
            RedundancyGroupSummary::on($this->getDb())
                ->filter(Filter::equal('id', $this->item->id))
                ->first()
        ));
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
