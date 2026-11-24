<?php

// SPDX-FileCopyrightText: 2024 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Model\UnreachableParent;
use Icinga\Module\Icingadb\Widget\ItemList\ObjectList;
use Icinga\Module\Icingadb\Hook\ExtensionHook\ObjectDetailExtensionHook;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;

/**
 * Redundancy group details with a list of members
 */
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
     * Create a new RedundancyGroupDetail
     *
     * @param RedundancyGroup $group
     * @param ValidHtml $members The scoped member list and its controls
     */
    public function __construct(RedundancyGroup $group, protected readonly ValidHtml $members)
    {
        $this->group = $group;
    }

    /**
     * Create hook extensions
     *
     * @return array<int, ValidHtml>
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
        if ($this->group->state->is_reachable) {
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
            (new ObjectList($rootProblems))->setEmptyStateMessage(
                $this->translate('You are not authorized to view these objects.')
            )
        ];
    }

    protected function assemble(): void
    {
        $this->add(ObjectDetailExtensionHook::injectExtensions([
            0 => $this->createRootProblems(),
            510 => $this->members,
        ], $this->createExtensions()));
    }
}
