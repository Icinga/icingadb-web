<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Hook\ExtensionHook\ObjectDetailExtensionHook;
use Icinga\Module\Icingadb\Model\Usergroup;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\HorizontalKeyValue;

/**
 * Contact group details with a list of members
 */
class UsergroupDetail extends BaseHtmlElement
{
    use Auth;
    use Database;

    /** @var Usergroup The given user group */
    protected $usergroup;

    protected $defaultAttributes = ['class' => 'object-detail'];

    protected $tag = 'div';

    /**
     * Create a new UsergroupDetail
     *
     * @param Usergroup $usergroup
     * @param ValidHtml $users The scoped member list and its controls
     */
    public function __construct(Usergroup $usergroup, protected readonly ValidHtml $users)
    {
        $this->usergroup = $usergroup;
    }

    protected function createPrintHeader()
    {
        return [
            new HtmlElement('h2', null, Text::create(t('Details'))),
            new HorizontalKeyValue(t('Name'), $this->usergroup->name)
        ];
    }

    protected function createCustomVars(): array
    {
        $content = [new HtmlElement('h2', null, Text::create(t('Custom Variables')))];
        $flattenedVars = $this->usergroup->customvar_flat;
        $this->applyRestrictions($flattenedVars);

        $vars = $this->usergroup->customvar_flat->getModel()->unflattenVars($flattenedVars);
        if (! empty($vars)) {
            $content[] = new HtmlElement('div', Attributes::create([
                'id' => 'usergroup-customvars',
                'class' => 'collapsible',
                'data-visible-height' => 200
            ]), new CustomVarTable($vars, $this->usergroup));
        } else {
            $content[] = new EmptyState(t('No custom variables configured.'));
        }

        return $content;
    }

    /**
     * Create the contacts section
     *
     * @return ValidHtml[]
     */
    protected function createUserList(): array
    {
        return [$this->users];
    }

    protected function createExtensions(): array
    {
        return ObjectDetailExtensionHook::loadExtensions($this->usergroup);
    }

    protected function assemble()
    {
        if (getenv('ICINGAWEB_EXPORT_FORMAT') === 'pdf') {
            $this->add($this->createPrintHeader());
        }

        $this->add(ObjectDetailExtensionHook::injectExtensions([
            400 => $this->createCustomVars(),
            500 => $this->createUserList(),
        ], $this->createExtensions()));
    }
}
