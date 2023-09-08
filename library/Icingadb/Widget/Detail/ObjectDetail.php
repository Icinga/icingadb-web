<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Exception;
use Icinga\Application\ClassLoader;
use Icinga\Application\Hook;
use Icinga\Application\Hook\GrapherHook;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Application\Web;
use Icinga\Date\DateFormatter;
use Icinga\Exception\IcingaException;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Common\HostLinks;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\Macros;
use Icinga\Module\Icingadb\Compat\CompatHost;
use Icinga\Module\Icingadb\Model\CustomvarFlat;
use Icinga\Module\Icingadb\Web\Navigation\Action;
use Icinga\Module\Icingadb\Widget\MarkdownText;
use Icinga\Module\Icingadb\Common\ServiceLinks;
use Icinga\Module\Icingadb\Forms\Command\Object\ToggleObjectFeaturesForm;
use Icinga\Module\Icingadb\Hook\ActionsHook\ObjectActionsHook;
use Icinga\Module\Icingadb\Hook\ExtensionHook\ObjectDetailExtensionHook;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Model\Usergroup;
use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Widget\ItemList\DowntimeList;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Web\Widget\CopyToClipboard;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\HorizontalKeyValue;
use Icinga\Module\Icingadb\Widget\ItemList\CommentList;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use Icinga\Module\Icingadb\Widget\TagList;
use Icinga\Module\Monitoring\Hook\DetailviewExtensionHook;
use Icinga\Web\Navigation\Navigation;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Text;
use ipl\Orm\ResultSet;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

class ObjectDetail extends BaseHtmlElement
{
    use Auth;
    use Database;
    use Macros;

    protected $object;

    protected $compatObject;

    protected $objectType;

    protected $defaultAttributes = [
        // Class host-detail is kept as the grafana module's iframe.js depends on it
        'class' => ['object-detail', 'host-detail'],
        'data-pdfexport-page-breaks-at' => 'h2'
    ];

    protected $tag = 'div';

    public function __construct($object)
    {
        $this->object = $object;
        $this->objectType = $object instanceof Host ? 'host' : 'service';
    }

    protected function compatObject()
    {
        if ($this->compatObject === null) {
            $this->compatObject = CompatHost::fromModel($this->object);
        }

        return $this->compatObject;
    }

    protected function createPrintHeader()
    {
        $info = [new HorizontalKeyValue(t('Name'), $this->object->name)];

        if ($this->objectType === 'host') {
            $info[] = new HorizontalKeyValue(
                t('IPv4 Address'),
                $this->object->address ?: new EmptyState(t('None', 'address'))
            );
            $info[] = new HorizontalKeyValue(
                t('IPv6 Address'),
                $this->object->address6 ?: new EmptyState(t('None', 'address'))
            );
        }

        $info[] = new HorizontalKeyValue(t('State'), [
            $this->object->state->getStateTextTranslated(),
            ' ',
            new StateBall($this->object->state->getStateText())
        ]);

        $info[] = new HorizontalKeyValue(
            t('Last State Change'),
            DateFormatter::formatDateTime($this->object->state->last_state_change->getTimestamp())
        );

        return [
            new HtmlElement('h2', null, Text::create(
                $this->objectType === 'host' ? t('Host') : t('Service')
            )),
            $info
        ];
    }

    protected function createActions()
    {
        $this->fetchCustomVars();

        $navigation = new Navigation();
        $navigation->load('icingadb-' . $this->objectType . '-action');
        /** @var Action $item */
        foreach ($navigation as $item) {
            $item->setObject($this->object);
        }

        foreach ($this->object->action_url->first()->action_url ?? [] as $url) {
            $url = $this->expandMacros($url, $this->object);
            $navigation->addItem(
                Html::wantHtml([
                    // Add warning to links that open in new tabs, as recommended by WCAG20 G201
                    new Icon('external-link-alt', ['title' => t('Link opens in a new window')]),
                    $url
                ])->render(),
                [
                    'target'   => '_blank',
                    'url'      => $url,
                    'renderer' => [
                        'NavigationItemRenderer',
                        'escape_label' => false
                    ]
                ]
            );
        }

        $moduleActions = ObjectActionsHook::loadActions($this->object);

        $nativeExtensionProviders = [];
        foreach ($moduleActions->getContent() as $item) {
            if ($item->getAttributes()->has('data-icinga-module')) {
                $nativeExtensionProviders[$item->getAttributes()->get('data-icinga-module')->getValue()] = true;
            }
        }

        if (Icinga::app()->getModuleManager()->hasInstalled('monitoring')) {
            foreach (Hook::all('Monitoring\\' . ucfirst($this->objectType) . 'Actions') as $hook) {
                $moduleName = ClassLoader::extractModuleName(get_class($hook));
                if (! isset($nativeExtensionProviders[$moduleName])) {
                    try {
                        $navigation->merge($hook->getNavigation($this->compatObject()));
                    } catch (Exception $e) {
                        Logger::error("Failed to load legacy action hook: %s\n%s", $e, $e->getTraceAsString());
                        $navigation->addItem($moduleName, ['label' => IcingaException::describe($e), 'url' => '#']);
                    }
                }
            }
        }

        if ($moduleActions->isEmpty() && ($navigation->isEmpty() || ! $navigation->hasRenderableItems())) {
            return null;
        }

        return [
            Html::tag('h2', t('Actions')),
            new HtmlString($navigation->getRenderer()->setCssClass('object-detail-actions')->render()),
            $moduleActions->isEmpty() ? null : $moduleActions
        ];
    }

    protected function createCheckStatistics(): array
    {
        return [
            Html::tag('h2', t('Check Statistics')),
            new CheckStatistics($this->object)
        ];
    }

    protected function createComments(): array
    {
        if ($this->objectType === 'host') {
            $link = HostLinks::comments($this->object);
            $relations = ['host', 'host.state'];
        } else {
            $link = ServiceLinks::comments($this->object, $this->object->host);
            $relations = ['service', 'service.state', 'service.host', 'service.host.state'];
        }

        $comments = $this->object->comment
            ->with($relations)
            ->limit(3)
            ->peekAhead();
        // TODO: This should be automatically done by the model/resolver and added as ON condition
        $comments->filter(Filter::equal('object_type', $this->objectType));

        $comments = $comments->execute();
        /** @var ResultSet $comments */

        $content = [Html::tag('h2', t('Comments'))];

        if ($comments->hasResult()) {
            $content[] = (new CommentList($comments))->setObjectLinkDisabled()->setTicketLinkEnabled();
            $content[] = (new ShowMore($comments, $link))->setBaseTarget('_next');
        } else {
            $content[] = new EmptyState(t('No comments created.'));
        }

        return $content;
    }

    protected function createCustomVars(): array
    {
        $content = [Html::tag('h2', t('Custom Variables'))];

        $this->fetchCustomVars();
        $vars = (new CustomvarFlat())->unFlattenVars($this->object->customvar_flat);
        if (! empty($vars)) {
            $content[] = new HtmlElement('div', Attributes::create([
                'id' => $this->objectType . '-customvars',
                'class' => 'collapsible',
                'data-visible-height' => 200
            ]), new CustomVarTable($vars, $this->object));
        } else {
            $content[] = new EmptyState(t('No custom variables configured.'));
        }

        return $content;
    }

    protected function createDowntimes(): array
    {
        if ($this->objectType === 'host') {
            $link = HostLinks::downtimes($this->object);
            $relations = ['host', 'host.state'];
        } else {
            $link = ServiceLinks::downtimes($this->object, $this->object->host);
            $relations = ['service', 'service.state', 'service.host', 'service.host.state'];
        }

        $downtimes = $this->object->downtime
            ->with($relations)
            ->limit(3)
            ->peekAhead();
        // TODO: This should be automatically done by the model/resolver and added as ON condition
        $downtimes->filter(Filter::equal('object_type', $this->objectType));

        $downtimes = $downtimes->execute();
        /** @var ResultSet $downtimes */

        $content = [Html::tag('h2', t('Downtimes'))];

        if ($downtimes->hasResult()) {
            $content[] = (new DowntimeList($downtimes))->setObjectLinkDisabled()->setTicketLinkEnabled();
            $content[] = (new ShowMore($downtimes, $link))->setBaseTarget('_next');
        } else {
            $content[] = new EmptyState(t('No downtimes scheduled.'));
        }

        return $content;
    }

    protected function createGroups(): array
    {
        $groups = [Html::tag('h2', t('Groups'))];

        if ($this->objectType === 'host') {
            $hostgroups = [];
            if ($this->isPermittedRoute('hostgroups')) {
                $hostgroups = $this->object->hostgroup;
                $this->applyRestrictions($hostgroups);
            }

            $hostgroupList = new TagList();
            foreach ($hostgroups as $hostgroup) {
                $hostgroupList->addLink($hostgroup->display_name, Links::hostgroup($hostgroup));
            }

            $groups[] = $hostgroupList->hasContent()
                ? $hostgroupList
                : new EmptyState(t('Not a member of any host group.'));
        } else {
            $servicegroups = [];
            if ($this->isPermittedRoute('servicegroups')) {
                $servicegroups = $this->object->servicegroup;
                $this->applyRestrictions($servicegroups);
            }

            $servicegroupList = new TagList();
            foreach ($servicegroups as $servicegroup) {
                $servicegroupList->addLink($servicegroup->display_name, Links::servicegroup($servicegroup));
            }

            $groups[] = $servicegroupList->hasContent()
                ? $servicegroupList
                : new EmptyState(t('Not a member of any service group.'));
        }

        return $groups;
    }

    protected function createNotes()
    {
        $navigation = new Navigation();
        $notes = trim($this->object->notes);

        foreach ($this->object->notes_url->first()->notes_url ?? [] as $url) {
            $url = $this->expandMacros($url, $this->object);
            $navigation->addItem(
                Html::wantHtml([
                    // Add warning to links that open in new tabs, as recommended by WCAG20 G201
                    new Icon('external-link-alt', ['title' => t('Link opens in a new window')]),
                    $url
                ])->render(),
                [
                    'target'   => '_blank',
                    'url'      => $url,
                    'renderer'  => [
                        'NavigationItemRenderer',
                        'escape_label' => false
                    ]
                ]
            );
        }

        $content = [];

        if (! $navigation->isEmpty() && $navigation->hasRenderableItems()) {
            $content[] = new HtmlString($navigation->getRenderer()->setCssClass('object-detail-actions')->render());
        }

        if ($notes !== '') {
            $content[] = (new MarkdownText($notes))
                ->addAttributes([
                    'class'               => 'collapsible',
                    'data-visible-height' => 200,
                    'id'                  => $this->objectType . '-notes'
                ]);
        }

        if (empty($content)) {
            return null;
        }

        array_unshift($content, Html::tag('h2', t('Notes')));

        return $content;
    }

    protected function createNotifications(): array
    {
        list($users, $usergroups) = $this->getUsersAndUsergroups();

        $userList = new TagList();
        $usergroupList = new TagList();

        foreach ($users as $user) {
            $userList->addLink([new Icon(Icons::USER), $user->display_name], Links::user($user));
        }

        foreach ($usergroups as $usergroup) {
            $usergroupList->addLink(
                [new Icon(Icons::USERGROUP), $usergroup->display_name],
                Links::usergroup($usergroup)
            );
        }

        return [
            Html::tag('h2', t('Notifications')),
            new HorizontalKeyValue(
                t('Users'),
                $userList->hasContent() ? $userList : new EmptyState(t('No users configured.'))
            ),
            new HorizontalKeyValue(
                t('User Groups'),
                $usergroupList->hasContent()
                    ? $usergroupList
                    : new EmptyState(t('No user groups configured.'))
            )
        ];
    }

    protected function createPerformanceData(): array
    {
        $content[] = Html::tag('h2', t('Performance Data'));

        if (empty($this->object->state->performance_data)) {
            $content[] = new EmptyState(t('No performance data available.'));
        } else {
            $content[] = new HtmlElement(
                'div',
                Attributes::create(['id' => 'check-perfdata-' . $this->object->checkcommand_name]),
                new PerfDataTable($this->object->state->normalized_performance_data)
            );
        }

        return $content;
    }

    protected function createPluginOutput(): array
    {
        if (empty($this->object->state->output) && empty($this->object->state->long_output)) {
            $pluginOutput = new EmptyState(t('Output unavailable.'));
        } else {
            $pluginOutput = new PluginOutputContainer(PluginOutput::fromObject($this->object));
            CopyToClipboard::attachTo($pluginOutput);
        }

        return [
            Html::tag('h2', t('Plugin Output')),
            Html::tag(
                'div',
                [
                    'id'    => 'check-output-' . $this->object->checkcommand_name,
                    'class' => 'collapsible',
                    'data-visible-height' => 100
                ],
                $pluginOutput
            )
        ];
    }

    protected function createExtensions(): array
    {
        $extensions = ObjectDetailExtensionHook::loadExtensions($this->object);

        $nativeExtensionProviders = [];
        foreach ($extensions as $extension) {
            if ($extension instanceof BaseHtmlElement && $extension->getAttributes()->has('data-icinga-module')) {
                $nativeExtensionProviders[$extension->getAttributes()->get('data-icinga-module')->getValue()] = true;
            }
        }

        if (! Icinga::app()->getModuleManager()->hasInstalled('monitoring')) {
            return $extensions;
        }

        foreach (Hook::all('Grapher') as $grapher) {
            /** @var GrapherHook $grapher */
            $moduleName = ClassLoader::extractModuleName(get_class($grapher));

            if (isset($nativeExtensionProviders[$moduleName])) {
                continue;
            }

            try {
                $graph = HtmlString::create($grapher->getPreviewHtml($this->compatObject()));
            } catch (Exception $e) {
                Logger::error("Failed to load legacy grapher: %s\n%s", $e, $e->getTraceAsString());
                $graph = Text::create(IcingaException::describe($e));
            }

            $location = ObjectDetailExtensionHook::BASE_LOCATIONS[ObjectDetailExtensionHook::GRAPH_SECTION];
            while (isset($extensions[$location])) {
                $location++;
            }

            $extensions[$location] = $graph;
        }

        foreach (Hook::all('Monitoring\DetailviewExtension') as $extension) {
            /** @var DetailviewExtensionHook $extension */
            $moduleName = $extension->getModule()->getName();

            if (isset($nativeExtensionProviders[$moduleName])) {
                continue;
            }

            try {
                /** @var Web $app */
                $app = Icinga::app();
                $renderedExtension = $extension
                    ->setView($app->getViewRenderer()->view)
                    ->getHtmlForObject($this->compatObject());

                $extensionHtml = new HtmlElement(
                    'div',
                    Attributes::create([
                        'class' => 'icinga-module module-' . $moduleName,
                        'data-icinga-module' => $moduleName
                    ]),
                    HtmlString::create($renderedExtension)
                );
            } catch (Exception $e) {
                Logger::error("Failed to load legacy detail extension: %s\n%s", $e, $e->getTraceAsString());
                $extensionHtml = Text::create(IcingaException::describe($e));
            }

            $location = ObjectDetailExtensionHook::BASE_LOCATIONS[ObjectDetailExtensionHook::DETAIL_SECTION];
            while (isset($extensions[$location])) {
                $location++;
            }

            $extensions[$location] = $extensionHtml;
        }

        return $extensions;
    }

    protected function createFeatureToggles(): array
    {
        $form = new ToggleObjectFeaturesForm($this->object);

        if ($this->objectType === 'host') {
            $form->setAction(HostLinks::toggleFeatures($this->object)->getAbsoluteUrl());
        } else {
            $form->setAction(ServiceLinks::toggleFeatures($this->object, $this->object->host)->getAbsoluteUrl());
        }

        return [
            Html::tag('h2', t('Feature Commands')),
            $form
        ];
    }

    protected function getUsersAndUsergroups(): array
    {
        $users = [];
        $usergroups = [];
        $groupBy = false;

        if ($this->objectType === 'host') {
            $objectFilter = Filter::all(
                Filter::equal('notification.host_id', $this->object->id),
                Filter::unlike('notification.service_id', '*')
            );
            $objectFilter->metaData()->set('forceOptimization', false);
            $groupBy = true;
        } else {
            $objectFilter = Filter::equal(
                'notification.service_id',
                $this->object->id
            );
        }

        $userQuery = null;
        if ($this->isPermittedRoute('users')) {
            $userQuery = User::on($this->getDb());
            $userQuery->filter($objectFilter);
            $this->applyRestrictions($userQuery);
            if ($groupBy) {
                $userQuery->getSelectBase()->groupBy(['user.id']);
            }

            foreach ($userQuery as $user) {
                $users[$user->name] = $user;
            }
        }

        if ($this->isPermittedRoute('usergroups')) {
            $usergroupQuery = Usergroup::on($this->getDb());
            $usergroupQuery->filter($objectFilter);
            $this->applyRestrictions($usergroupQuery);
            if ($groupBy) {
                $userQuery->getSelectBase()->groupBy(['usergroup.id']);
            }

            foreach ($usergroupQuery as $usergroup) {
                $usergroups[$usergroup->name] = $usergroup;
            }
        }

        return [$users, $usergroups];
    }

    protected function fetchCustomVars()
    {
        $customvarFlat = $this->object->customvar_flat;
        if (! $customvarFlat instanceof ResultSet) {
            $this->applyRestrictions($customvarFlat);
            $customvarFlat->withColumns(['customvar.name', 'customvar.value']);
            $this->object->customvar_flat = $customvarFlat->execute();
        }
    }
}
