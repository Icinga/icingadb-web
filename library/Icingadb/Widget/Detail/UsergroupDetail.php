<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Hook\ExtensionHook\ObjectDetailExtensionHook;
use Icinga\Module\Icingadb\Model\Usergroup;
use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Module\Icingadb\Widget\ItemTable\UserTable;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\HorizontalKeyValue;

class UsergroupDetail extends BaseHtmlElement
{
    use Auth;
    use Database;

    /** @var Usergroup The given user group */
    protected $usergroup;

    protected $defaultAttributes = ['class' => 'object-detail'];

    protected $tag = 'div';

    public function __construct(Usergroup $usergroup)
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

    protected function createUserList(): array
    {
        $users = $this->usergroup->user->limit(6)->peekAhead()->execute();

        $showMoreLink = (new ShowMore(
            $users,
            Links::users()->addParams(['usergroup.name' => $this->usergroup->name])
        ))->setBaseTarget('_next');

        return [
            new HtmlElement('h2', null, Text::create(t('Users'))),
            new UserTable($users),
            $showMoreLink
        ];
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
            500 => $this->createUserList(),
            700 => $this->createCustomVars()
        ], $this->createExtensions()));
    }
}
