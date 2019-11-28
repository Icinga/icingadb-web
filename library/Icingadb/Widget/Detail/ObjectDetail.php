<?php

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\HostLinks;
use Icinga\Module\Icingadb\Common\HostStates;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\ServiceLinks;
use Icinga\Module\Icingadb\Common\ServiceStates;
use Icinga\Module\Icingadb\Compat\CompatBackend;
use Icinga\Module\Icingadb\Compat\CompatHost;
use Icinga\Module\Icingadb\Compat\CompatObject;
use Icinga\Module\Icingadb\Compat\CompatPluginOutput;
use Icinga\Module\Icingadb\Compat\CompatService;
use Icinga\Module\Icingadb\Compat\CustomvarFilter;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Widget\DowntimeList;
use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Module\Icingadb\Widget\HorizontalKeyValue;
use Icinga\Module\Icingadb\Widget\ItemList\CommentList;
use Icinga\Module\Icingadb\Widget\ShowMore;
use Icinga\Module\Icingadb\Widget\TagList;
use Icinga\Module\Monitoring\Forms\Command\Object\ToggleObjectFeaturesCommandForm;
use Icinga\Module\Monitoring\Hook\ObjectActionsHook;
use Icinga\Web\Helper\Markdown;
use Icinga\Web\Hook;
use Icinga\Web\Navigation\Navigation;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Orm\ResultSet;
use ipl\Web\Widget\Icon;
use Zend_View_Helper_Perfdata;

class ObjectDetail extends BaseHtmlElement
{
    use Auth;

    protected $object;

    protected $objectType;

    protected $defaultAttributes = ['class' => 'host-detail'];

    protected $tag = 'div';

    public function __construct($object)
    {
        $this->object = $object;
        $this->objectType = $object instanceof Host ? 'host' : 'service';
    }

    protected function createActions()
    {
        $compatObject = CompatObject::fromModel($this->object);
        $navigation = new Navigation();
        $navigation->load($this->objectType . '-action');
        foreach ($navigation as $item) {
            $item->setObject($compatObject);
        }

        foreach ($compatObject->getActionUrls() as $i => $url) {
            $navigation->addItem(
                'Action ' . ($i + 1),
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
            $navigation->merge($hook->getNavigation($compatObject));
        }

        if ($navigation->isEmpty() || ! $navigation->hasRenderableItems()) {
            return null;
        }

        return [
            Html::tag('h2', 'Actions'),
            new HtmlString($navigation->getRenderer()->render())
        ];
    }

    protected function createCheckStatistics()
    {
        return [
            Html::tag('h2', 'Check Statistics'),
            new CheckStatistics($this->object)
        ];
    }

    protected function createComments()
    {
        if ($this->objectType === 'host') {
            $link = HostLinks::comments($this->object);
        } else {
            $link = ServiceLinks::comments($this->object, $this->object->host);
        }

        /** @var ResultSet $comments */
        $comments = $this->object->comment->limit(3)->peekAhead()->execute();

        $content = [Html::tag('h2', 'Comments')];

        if ($comments->hasResult()) {
            $content[] = new CommentList($comments);
            $content[] = new ShowMore($comments, $link);
        } else {
            $content[] = new EmptyState('No comments created.');
        }

        return $content;
    }

    protected function createCustomVars()
    {
        $content = [Html::tag('h2', 'Custom Variables')];
        $vars = $this->object->customvar->execute();

        if ($vars->hasResult()) {
            $vars = new CustomvarFilter(
                $vars,
                $this->objectType,
                $this->getAuth()->getRestrictions('monitoring/blacklist/properties'),
                Config::module('monitoring')->get('security', 'protected_customvars', '')
            );

            $content[] = new CustomVarTable($vars);
        } else {
            $content[] = new EmptyState('No custom variables configured.');
        }

        return $content;
    }

    protected function createDowntimes()
    {
        if ($this->objectType === 'host') {
            $link = HostLinks::downtimes($this->object);
        } else {
            $link = ServiceLinks::downtimes($this->object, $this->object->host);
        }

        $downtimes = $this->object->downtime->limit(3)->peekAhead()->execute();

        $content = [Html::tag('h2', 'Downtimes')];

        if ($downtimes->hasResult()) {
            $content[] = new DowntimeList($downtimes);
            $content[] = new ShowMore($downtimes, $link);
        } else {
            $content[] = new EmptyState('No downtimes scheduled.');
        }

        return $content;
    }

    protected function createEvents()
    {
    }

    protected function createGroups()
    {
        $groups = [Html::tag('h2', 'Groups')];

        if ($this->objectType === 'host') {
            $hostgroupList = new TagList();

            foreach ($this->object->hostgroup as $hostgroup) {
                $hostgroupList->addLink($hostgroup->display_name, Links::hostgroup($hostgroup));
            }

            $groups[] = new HorizontalKeyValue(
                'Host Groups',
                $hostgroupList->hasContent()
                    ? $hostgroupList
                    : new EmptyState('Not a member of any host group.')
            );
        } else {
            $servicegroupList = new TagList();

            foreach ($this->object->servicegroup as $servicegroup) {
                $servicegroupList->addLink($servicegroup->display_name, Links::servicegroup($servicegroup));
            }

            $groups[] = new HorizontalKeyValue(
                'Service Groups',
                $servicegroupList->hasContent()
                    ? $servicegroupList
                    : new EmptyState('Not a member of any service group.')
            );
        }

        return $groups;
    }

    protected function createNotes()
    {
        $compatObject = CompatObject::fromModel($this->object);
        $navigation = new Navigation();
        $notes = trim($this->object->notes);

        foreach ($compatObject->getNotesUrls() as $i => $url) {
            $navigation->addItem(
                'Notes ' . ($i + 1),
                [
                    'renderer' => 'NavigationItemRenderer',
                    'target'   => '_blank',
                    'url'      => $url
                ]
            );
        }

        $content = [];

        if (! $navigation->isEmpty() && $navigation->hasRenderableItems()) {
            $content[] = new HtmlString($navigation->getRenderer()->render());
        }

        if ($notes !== '') {
            $content[] = Html::tag(
                'section',
                [
                    'class'               => 'markdown collapsible',
                    'data-visible-height' => 200,
                    'id'                  => $this->objectType . '-notes'
                ],
                Markdown::text($notes)
            );
        }

        if (empty($content)) {
            return null;
        }

        array_unshift($content, Html::tag('h2', 'Notes'));

        return $content;
    }

    protected function createNotifications()
    {
        list($users, $usergroups) = $this->getUsersAndUsergroups();

        $userList = new TagList();
        $usergroupList = new TagList();

        foreach ($users as $user) {
            $userList->addLink(
                [new Icon(Icons::USER), $user->display_name], Links::user($user)
            );
        }

        foreach ($usergroups as $usergroup) {
            $usergroupList->addLink(
                [new Icon(Icons::USERGROUP), $usergroup->display_name], Links::usergroup($usergroup)
            );
        }

        return [
            Html::tag('h2', 'Notifications'),
            new HorizontalKeyValue(
                'Users',
                $userList->hasContent() ? $userList : new EmptyState('No users configured.')
            ),
            new HorizontalKeyValue(
                'User Groups',
                $usergroupList->hasContent() ? $usergroupList : new EmptyState('No user groups configured.')
            )
        ];
    }

    protected function createPerformanceData()
    {
        require_once Icinga::app()->getModuleManager()->getModule('monitoring')->getBaseDir()
            . '/application/views/helpers/Perfdata.php';

        $helper = new Zend_View_Helper_Perfdata();
        $helper->view = Icinga::app()->getViewRenderer()->view;

        $content[] = Html::tag('h2', 'Performance Data');

        if (empty($this->object->state->performance_data)) {
            $content[] = new EmptyState('No performance data available.');
        } else {
            $content[] = new HtmlString($helper->perfdata($this->object->state->performance_data));
        }

        return $content;
    }

    protected function createPluginOutput()
    {
        if ($this->objectType === 'host') {
            $state = HostStates::text($this->object->state->soft_state);
        } else {
            $state = ServiceStates::text($this->object->state->soft_state);
        }
        return [
            Html::tag('h2', 'Plugin Output'),
            Html::tag('div', ['class' => 'collapsible'],
                CompatPluginOutput::getInstance()->render(
                    $this->object->state->output . "\n" . $this->object->state->long_output
                )
            )
        ];
    }

    protected function createFeatureToggles()
    {
        $form = new ToggleObjectFeaturesCommandForm([
            'backend'   => new CompatBackend()
        ]);

        if ($this->objectType === 'host') {
            $form->load(new CompatHost($this->object));
            $form->setAction(HostLinks::toggleFeatures($this->object));
        } else {
            $form->load(new CompatService($this->object));
            $form->setAction(ServiceLinks::toggleFeatures($this->object, $this->object->host));
        }

        return [
            Html::tag('h2', 'Feature Commands'),
            HtmlString::create($form->render())
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
            foreach ($this->object->notification as $notification) {
                foreach ($notification->user as $user) {
                    $users[$user->name] = $user;
                }

                foreach ($notification->usergroup as $usergroup) {
                    $usergroups[$usergroup->name] = $usergroup;
                }
            }
        }

        return [$users, $usergroups];
    }

    protected function assemble()
    {
        $this->add([
            $this->createPluginOutput(),
            $this->createEvents(),
            $this->createActions(),
            $this->createNotes(),
            $this->createGroups(),
            $this->createComments(),
            $this->createDowntimes(),
            $this->createNotifications(),
            $this->createCheckStatistics(),
            $this->createPerformanceData(),
            $this->createCustomVars(),
            $this->createFeatureToggles()
        ]);
    }
}
