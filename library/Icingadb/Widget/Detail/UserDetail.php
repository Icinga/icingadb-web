<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Hook\ExtensionHook\ObjectDetailExtensionHook;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Module\Icingadb\Widget\HorizontalKeyValue;
use Icinga\Module\Icingadb\Widget\ItemList\UsergroupList;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

class UserDetail extends BaseHtmlElement
{
    use Auth;
    use Database;

    /** @var User The given user */
    protected $user;

    protected $defaultAttributes = ['class' => 'user-detail'];

    protected $tag = 'div';

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    protected function createCustomVars()
    {
        $content = [new HtmlElement('h2', null, Text::create(t('Custom Variables')))];
        $flattenedVars = $this->user->customvar_flat;
        $this->applyRestrictions($flattenedVars);

        $vars = $this->user->customvar_flat->getModel()->unflattenVars($flattenedVars);
        if (! empty($vars)) {
            $customvarTable = new CustomVarTable($vars);
            $customvarTable->setAttribute('id', 'user-customvars');
            $content[] = $customvarTable;
        } else {
            $content[] = new EmptyState(t('No custom variables configured.'));
        }

        return $content;
    }

    protected function createUserDetail()
    {
        return [
            new HtmlElement('h2', null, Text::create(t('Details'))),
            new HorizontalKeyValue(t('E-Mail'), $this->user->email),
            new HorizontalKeyValue(t('Pager'), $this->user->pager)
        ];
    }

    protected function createUsergroupList()
    {
        $userGroups = $this->user->usergroup->limit(6)->peekAhead()->execute();

        $showMoreLink = (new ShowMore(
            $userGroups,
            Links::usergroups()->addParams(['user.name' => $this->user->name])
        ))->setBaseTarget('_next');

        return [
            new HtmlElement('h2', null, Text::create(t('Groups'))),
            new UsergroupList($userGroups),
            $showMoreLink
        ];
    }

    protected function createExtensions()
    {
        return ObjectDetailExtensionHook::loadExtensions($this->user);
    }

    protected function assemble()
    {
        $this->add(ObjectDetailExtensionHook::injectExtensions([
            200 => $this->createUserDetail(),
            500 => $this->createUsergroupList(),
            700 => $this->createCustomVars()
        ], $this->createExtensions()));
    }
}
