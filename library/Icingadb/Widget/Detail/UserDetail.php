<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Hook\ExtensionHook\ObjectDetailExtensionHook;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Widget\EmptyState;
use ipl\Html\Attributes;
use ipl\Web\Widget\HorizontalKeyValue;
use Icinga\Module\Icingadb\Widget\ItemTable\UsergroupTable;
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

    protected $defaultAttributes = ['class' => 'object-detail'];

    protected $tag = 'div';

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    protected function createCustomVars(): array
    {
        $content = [new HtmlElement('h2', null, Text::create(t('Custom Variables')))];
        $flattenedVars = $this->user->customvar_flat;
        $this->applyRestrictions($flattenedVars);

        $vars = $this->user->customvar_flat->getModel()->unflattenVars($flattenedVars);
        if (! empty($vars)) {
            $content[] = new HtmlElement('div', Attributes::create([
                'id' => 'user-customvars',
                'class' => 'collapsible',
                'data-visible-height' => 200
            ]), new CustomVarTable($vars, $this->user));
        } else {
            $content[] = new EmptyState(t('No custom variables configured.'));
        }

        return $content;
    }

    protected function createUserDetail(): array
    {
        list($hostStates, $serviceStates) = $this->separateStates($this->user->states);
        $hostStates = implode(', ', $this->localizeStates($hostStates));
        $serviceStates = implode(', ', $this->localizeStates($serviceStates));
        $types = implode(', ', $this->localizeTypes($this->user->types));

        return [
            new HtmlElement('h2', null, Text::create(t('Details'))),
            new HorizontalKeyValue(t('Name'), $this->user->name),
            new HorizontalKeyValue(t('E-Mail'), $this->user->email ?: new EmptyState(t('None', 'address'))),
            new HorizontalKeyValue(t('Pager'), $this->user->pager ?: new EmptyState(t('None', 'phone-number'))),
            new HorizontalKeyValue(t('Host States'), $hostStates ?: t('All')),
            new HorizontalKeyValue(t('Service States'), $serviceStates ?: t('All')),
            new HorizontalKeyValue(t('Types'), $types ?: t('All'))
        ];
    }

    protected function createUsergroupList(): array
    {
        $userGroups = $this->user->usergroup->limit(6)->peekAhead()->execute();

        $showMoreLink = (new ShowMore(
            $userGroups,
            Links::usergroups()->addParams(['user.name' => $this->user->name])
        ))->setBaseTarget('_next');

        return [
            new HtmlElement('h2', null, Text::create(t('Groups'))),
            new UsergroupTable($userGroups),
            $showMoreLink
        ];
    }

    protected function createExtensions(): array
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

    private function localizeTypes(array $types): array
    {
        $localizedTypes = [];
        foreach ($types as $type) {
            switch ($type) {
                case 'problem':
                    $localizedTypes[] = t('Problem');
                    break;
                case 'ack':
                    $localizedTypes[] = t('Acknowledgement');
                    break;
                case 'recovery':
                    $localizedTypes[] = t('Recovery');
                    break;
                case 'downtime_start':
                    $localizedTypes[] = t('Downtime Start');
                    break;
                case 'downtime_end':
                    $localizedTypes[] = t('Downtime End');
                    break;
                case 'downtime_removed':
                    $localizedTypes[] = t('Downtime Removed');
                    break;
                case 'flapping_start':
                    $localizedTypes[] = t('Flapping Start');
                    break;
                case 'flapping_end':
                    $localizedTypes[] = t('Flapping End');
                    break;
                case 'custom':
                    $localizedTypes[] = t('Custom');
                    break;
            }
        }

        return $localizedTypes;
    }

    private function localizeStates(array $states): array
    {
        $localizedState = [];
        foreach ($states as $state) {
            switch ($state) {
                case 'up':
                    $localizedState[] = t('Up');
                    break;
                case 'down':
                    $localizedState[] = t('Down');
                    break;
                case 'ok':
                    $localizedState[] = t('Ok');
                    break;
                case 'warning':
                    $localizedState[] = t('Warning');
                    break;
                case 'critical':
                    $localizedState[] = t('Critical');
                    break;
                case 'unknown':
                    $localizedState[] = t('Unknown');
                    break;
            }
        }

        return $localizedState;
    }

    private function separateStates(array $states): array
    {
        $hostStates = [];
        $serviceStates = [];

        foreach ($states as $state) {
            if ($state === 'Up' || $state === 'Down') {
                $hostStates[] = $state;
            } else {
                $serviceStates[] = $state;
            }
        }

        return [$hostStates, $serviceStates];
    }
}
