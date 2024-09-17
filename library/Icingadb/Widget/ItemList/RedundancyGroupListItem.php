<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Common\ListItemCommonLayout;
use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Model\RedundancyGroupParentStateSummary;
use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use Icinga\Module\Icingadb\Widget\ObjectsStatistics;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\StateBall;
use ipl\Html\HtmlElement;
use ipl\Html\Attributes;
use ipl\Html\Text;
use ipl\Web\Widget\TimeSince;

/**
 * Redundancy group list item of a root problem list. Represents one database row.
 *
 * @property RedundancyGroup $item
 */
class RedundancyGroupListItem extends StateListItem
{
    use ListItemCommonLayout;
    use Auth;
    use Database;

    protected $baseAttributes = ['class' => ['list-item', 'redundancy-group-list-item']];

    protected function getStateBallSize(): string
    {
        return StateBall::SIZE_LARGE;
    }

    protected function createTimestamp(): ?BaseHtmlElement
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
        $visual->addHtml(new StateBall($this->item->state->getStateText(), $this->getStateBallSize()));
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $filter = Filter::equal('id', $this->item->id);
        $relations = [
            'from',
            'from.to.host',
            'from.to.host.state',
            'from.to.service',
            'from.to.service.state'
        ];

        $summary = RedundancyGroupParentStateSummary::on($this->getDb())
            ->with($relations)
            ->filter($filter);

        $members = RedundancyGroup::on($this->getDb())
            ->columns([
                'id' => 'id',
                'objects_output' => new Expression(
                    'CASE WHEN redundancy_group_from_to_host_state.output IS NULL'
                    . ' THEN redundancy_group_from_to_service_state.output'
                    . ' ELSE redundancy_group_from_to_host_state.output END'
                ),
                'objects_long_output' => new Expression(
                    'CASE WHEN redundancy_group_from_to_host_state.long_output IS NULL'
                    . ' THEN redundancy_group_from_to_service_state.long_output'
                    . ' ELSE redundancy_group_from_to_host_state.long_output END'
                ),
                'objects_checkcommand_name' => new Expression(
                    'CASE WHEN redundancy_group_from_to_host.checkcommand_name IS NULL'
                    . ' THEN redundancy_group_from_to_service.checkcommand_name'
                    . ' ELSE redundancy_group_from_to_host.checkcommand_name END'
                ),
                'objects_last_state_change' => new Expression(
                    'CASE WHEN redundancy_group_from_to_host_state.last_state_change IS NULL'
                    . ' THEN redundancy_group_from_to_service_state.last_state_change'
                    . ' ELSE redundancy_group_from_to_host_state.last_state_change END'
                ),
                'objects_severity' => new Expression(
                    'CASE WHEN redundancy_group_from_to_host_state.severity IS NULL'
                    . ' THEN redundancy_group_from_to_service_state.severity'
                    . ' ELSE redundancy_group_from_to_host_state.severity END'
                )
            ])
            ->with($relations)
            ->filter($filter)
            ->orderBy([
                'objects_severity',
                'objects_last_state_change',
            ], SORT_DESC);

        $this->applyRestrictions($members);

        /** @var RedundancyGroup $data */
        $data = $members->first();

        if($data) {
            $caption->addHtml(new PluginOutputContainer(
                (new PluginOutput($data->objects_output . "\n" .$data->objects_long_output))
                    ->setCommandName($data->objects_checkcommand_name)
            ));
        }

        $caption->addHtml(new ObjectsStatistics($summary->first()));
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $subject = $this->createSubject();
        if ($this->state->failed) {
            $stateTextElement = Html::sprintf(
                t('%s has %s', '<hostname> has <state-text>'),
                $subject,
                new HtmlElement('span', Attributes::create(['class' => 'state-text']), Text::create('FAILED'))
            );
        } else {
            $stateTextElement = Html::sprintf(
                t('%s is %s', '<hostname> is <state-text>'),
                $subject,
                new HtmlElement('span', Attributes::create(['class' => 'state-text']), Text::create('OK'))
            );
        }

        $title->addHtml($stateTextElement);
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
