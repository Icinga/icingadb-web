<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Application\Icinga;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Common\HostLinks;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\MarkdownText;
use Icinga\Module\Icingadb\Common\ServiceLinks;
use Icinga\Module\Icingadb\Compat\CompatObject;
use Icinga\Module\Icingadb\Compat\CompatPluginOutput;
use Icinga\Module\Icingadb\Forms\Command\Object\ToggleObjectFeaturesForm;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Model\Usergroup;
use Icinga\Module\Icingadb\Widget\DowntimeList;
use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Module\Icingadb\Widget\HorizontalKeyValue;
use Icinga\Module\Icingadb\Widget\ItemList\CommentList;
use Icinga\Module\Icingadb\Widget\ShowMore;
use Icinga\Module\Icingadb\Widget\TagList;
use Icinga\Module\Monitoring\Hook\DetailviewExtensionHook;
use Icinga\Module\Monitoring\Hook\ObjectActionsHook;
use Icinga\Web\Hook;
use Icinga\Web\Navigation\Navigation;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Orm\Compat\FilterProcessor;
use ipl\Orm\ResultSet;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Icon;
use Zend_View_Helper_Perfdata;

class ObjectDetail extends BaseHtmlElement
{
    use Auth;
    use Database;

    protected $object;

    protected $compatObject;

    protected $objectType;

    protected $defaultAttributes = ['class' => 'host-detail'];

    protected $tag = 'div';

    public function __construct($object)
    {
        $this->object = $object;
        $this->compatObject = CompatObject::fromModel($object);
        $this->objectType = $object instanceof Host ? 'host' : 'service';
    }

    protected function createActions()
    {
        $navigation = new Navigation();
        $navigation->load($this->objectType . '-action');
        foreach ($navigation as $item) {
            $item->setObject($this->compatObject);
        }

        foreach ($this->compatObject->getActionUrls() as $i => $url) {
            $navigation->addItem(
                sprintf(t('Action %d'), $i + 1),
                [
                    'renderer' => [
                        'NavigationItemRenderer',
                        'escape_label' => false
                    ],
                    'target'   => '_blank',
                    'url'      => $url
                ]
            );
        }

        /** @var ObjectActionsHook $hook */
        foreach (Hook::all('Monitoring\\' . ucfirst($this->objectType) . 'Actions') as $hook) {
            $navigation->merge($hook->getNavigation($this->compatObject));
        }

        if ($navigation->isEmpty() || ! $navigation->hasRenderableItems()) {
            return null;
        }

        return [
            Html::tag('h2', t('Actions')),
            new HtmlString($navigation->getRenderer()->setCssClass('actions')->render())
        ];
    }

    protected function createCheckStatistics()
    {
        return [
            Html::tag('h2', t('Check Statistics')),
            new CheckStatistics($this->object)
        ];
    }

    protected function createComments()
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
        FilterProcessor::apply(Filter::equal('object_type', $this->objectType), $comments);

        $comments = $comments->execute();
        /** @var ResultSet $comments */

        $content = [Html::tag('h2', t('Comments'))];

        if ($comments->hasResult()) {
            $content[] = new CommentList($comments);
            $content[] = new ShowMore($comments, $link);
        } else {
            $content[] = new EmptyState(t('No comments created.'));
        }

        return $content;
    }

    protected function createCustomVars()
    {
        $content = [Html::tag('h2', t('Custom Variables'))];
        $flattenedVars = $this->object->customvar_flat;
        $this->applyRestrictions($flattenedVars);

        $vars = $this->object->customvar_flat->getModel()->unflattenVars($flattenedVars);
        if (! empty($vars)) {
            $customvarTable = new CustomVarTable($vars);
            $customvarTable->setAttribute('id', $this->objectType . '-customvars');
            $content[] = $customvarTable;
        } else {
            $content[] = new EmptyState(t('No custom variables configured.'));
        }

        return $content;
    }

    protected function createDowntimes()
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
        FilterProcessor::apply(Filter::equal('object_type', $this->objectType), $downtimes);

        $downtimes = $downtimes->execute();
        /** @var ResultSet $downtimes */

        $content = [Html::tag('h2', t('Downtimes'))];

        if ($downtimes->hasResult()) {
            $content[] = new DowntimeList($downtimes);
            $content[] = new ShowMore($downtimes, $link);
        } else {
            $content[] = new EmptyState(t('No downtimes scheduled.'));
        }

        return $content;
    }

    protected function createGroups()
    {
        $groups = [Html::tag('h2', t('Groups'))];

        if ($this->objectType === 'host') {
            $hostgroups = $this->object->hostgroup;
            $this->applyRestrictions($hostgroups);

            $hostgroupList = new TagList();
            foreach ($hostgroups as $hostgroup) {
                $hostgroupList->addLink($hostgroup->display_name, Links::hostgroup($hostgroup));
            }

            $groups[] = new HorizontalKeyValue(
                t('Host Groups'),
                $hostgroupList->hasContent()
                    ? $hostgroupList
                    : new EmptyState(t('Not a member of any host group.'))
            );
        } else {
            $servicegroups = $this->object->servicegroup;
            $this->applyRestrictions($servicegroups);

            $servicegroupList = new TagList();
            foreach ($servicegroups as $servicegroup) {
                $servicegroupList->addLink($servicegroup->display_name, Links::servicegroup($servicegroup));
            }

            $groups[] = new HorizontalKeyValue(
                t('Service Groups'),
                $servicegroupList->hasContent()
                    ? $servicegroupList
                    : new EmptyState(t('Not a member of any service group.'))
            );
        }

        return $groups;
    }

    protected function createNotes()
    {
        $navigation = new Navigation();
        $notes = trim($this->object->notes);

        foreach ($this->compatObject->getNotesUrls() as $i => $url) {
            $navigation->addItem(
                sprintf(t('Note %d'), $i + 1),
                [
                    'renderer' => 'NavigationItemRenderer',
                    'target'   => '_blank',
                    'url'      => $url
                ]
            );
        }

        $content = [];

        if (! $navigation->isEmpty() && $navigation->hasRenderableItems()) {
            $content[] = new HtmlString($navigation->getRenderer()->setCssClass('actions')->render());
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

    protected function createNotifications()
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

    protected function createPerformanceData()
    {
        require_once Icinga::app()->getModuleManager()->getModule('monitoring')->getBaseDir()
            . '/application/views/helpers/Perfdata.php';

        $helper = new Zend_View_Helper_Perfdata();
        $helper->view = Icinga::app()->getViewRenderer()->view;

        $content[] = Html::tag('h2', t('Performance Data'));

        if (empty($this->object->state->performance_data)) {
            $content[] = new EmptyState(t('No performance data available.'));
        } else {
            $content[] = new HtmlElement(
                'div',
                ['id' => 'check-perfdata-' . $this->object->checkcommand],
                new HtmlString($helper->perfdata($this->object->state->performance_data))
            );
        }

        return $content;
    }

    protected function createPluginOutput()
    {
        if (empty($this->object->state->output) && empty($this->object->state->long_output)) {
            $pluginOutput = new EmptyState(t('Output unavailable.'));
        } else {
            $pluginOutput = CompatPluginOutput::getInstance()->render(
                $this->object->state->output . "\n" . $this->object->state->long_output
            );
        }

        return [
            Html::tag('h2', t('Plugin Output')),
            Html::tag(
                'div',
                [
                    'id'    => 'check-output-' . $this->object->checkcommand,
                    'class' => 'collapsible',
                    'data-visible-height' => 100
                ],
                $pluginOutput
            )
        ];
    }

    protected function createExtensions()
    {
        $extensions = Hook::all('Monitoring\DetailviewExtension');

        $html = [];
        foreach ($extensions as $extension) {
            /** @var DetailviewExtensionHook $extension */

            try {
                $renderedExtension = $extension
                    ->setView(Icinga::app()->getViewRenderer()->view)
                    ->getHtmlForObject($this->compatObject);

                $extensionHtml = new HtmlElement(
                    'div',
                    [
                        'class' => 'icinga-module module-' . $extension->getModule()->getName(),
                        'data-icinga-module' => $extension->getModule()->getName()
                    ],
                    HtmlString::create($renderedExtension)
                );
            } catch (Exception $e) {
                Logger::error("Failed to load extension: %s\n%s", $e, $e->getTraceAsString());
                $extensionHtml = Text::create(IcingaException::describe($e));
            }

            $html[] = $extensionHtml;
        }

        return $html;
    }

    protected function createFeatureToggles()
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

    protected function getUsersAndUsergroups()
    {
        $users = [];
        $usergroups = [];

        if (
            $this->getAuth()->hasPermission('*')
            || ! $this->getAuth()->hasPermission('no-monitoring/contacts')
        ) {
            $objectFilter = Filter::equal(
                'notification.' . ($this->objectType === 'host' ? 'host_id' : 'service_id'),
                $this->object->id
            );

            $userQuery = User::on($this->getDb());
            FilterProcessor::apply($objectFilter, $userQuery);
            $this->applyRestrictions($userQuery);
            foreach ($userQuery as $user) {
                $users[$user->name] = $user;
            }

            $usergroupQuery = Usergroup::on($this->getDb());
            FilterProcessor::apply($objectFilter, $usergroupQuery);
            $this->applyRestrictions($usergroupQuery);
            foreach ($usergroupQuery as $usergroup) {
                $usergroups[$usergroup->name] = $usergroup;
            }
        }

        return [$users, $usergroups];
    }
}
