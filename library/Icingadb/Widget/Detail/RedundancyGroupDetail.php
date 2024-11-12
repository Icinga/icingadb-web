<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\DependencyNode;
use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Model\UnreachableParent;
use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use Icinga\Module\Icingadb\Widget\ItemList\DependencyNodeList;
use Icinga\Module\Icingadb\Hook\ExtensionHook\ObjectDetailExtensionHook;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Url;

class RedundancyGroupDetail extends BaseHtmlElement
{
    use Auth;
    use Database;
    use Translation;

    /** @var RedundancyGroup The redundancy group */
    protected $group;

    protected $defaultAttributes = [
        'class' => ['redundancygroup-detail'],
        'data-pdfexport-page-breaks-at' => 'h2'
    ];

    protected $tag = 'div';

    /**
     * Create a new redundancy group detail widget
     *
     * @param RedundancyGroup $group
     */
    public function __construct(RedundancyGroup $group)
    {
        $this->group = $group;
    }

    /**
     * Create hook extensions
     *
     * @return array
     */
    protected function createExtensions(): array
    {
        return ObjectDetailExtensionHook::loadExtensions($this->group);
    }

    /**
     * Create a list of root problems if the redundancy group fails
     *
     * @return ?BaseHtmlElement[]
     */
    protected function createRootProblems(): ?array
    {
        if (! $this->group->state->failed) {
            return null;
        }

        $rootProblems = UnreachableParent::on($this->getDb(), $this->group)
            ->with([
                'redundancy_group',
                'redundancy_group.state',
                'host',
                'host.state',
                'host.icon_image',
                'host.state.last_comment',
                'service',
                'service.state',
                'service.icon_image',
                'service.state.last_comment',
                'service.host',
                'service.host.state',
            ])
            ->setResultSetClass(VolatileStateResults::class)
            ->orderBy([
                'host.state.severity',
                'host.state.last_state_change',
                'service.state.severity',
                'service.state.last_state_change',
                'redundancy_group.state.failed',
                'redundancy_group.state.last_state_change'
            ], SORT_DESC);

        $this->applyRestrictions($rootProblems);

        return [
            HtmlElement::create('h2', null, Text::create($this->translate('Root Problems'))),
            (new DependencyNodeList($rootProblems))->setEmptyStateMessage(
                $this->translate('You are not authorized to view these objects.')
            )
        ];
    }

    /**
     * Create a list of group members
     *
     * @return BaseHtmlElement[]
     */
    protected function createGroupMembers(): array
    {
        $membersQuery = DependencyNode::on($this->getDb())
            ->with([
                'host',
                'host.state',
                'service',
                'service.state',
                'service.host',
                'service.host.state'
            ])
            ->filter(Filter::equal('child.redundancy_group.id', $this->group->id))
            ->limit(5)
            ->peekAhead();

        $this->applyRestrictions($membersQuery);

        // TODO: Do not execute at this time. The widget may be replaced by a hook in which case the result is unused.
        $members = $membersQuery->execute();

        return [
            HtmlElement::create('h2', null, Text::create($this->translate('Group Members'))),
            (new DependencyNodeList($members))
                ->setEmptyStateMessage($this->translate('You are not authorized to view these objects.')),
            (new ShowMore($members, Url::fromPath('icingadb/redundancygroup/members', ['id' => $this->group->id])))
                ->setBaseTarget('_self')
        ];
    }

    protected function assemble(): void
    {
        $this->add(ObjectDetailExtensionHook::injectExtensions([
            0 => $this->createRootProblems(),
            510 => $this->createGroupMembers(),
        ], $this->createExtensions()));
    }
}
