<?php

namespace Icinga\Module\Eagle\Widget\Detail;

use Icinga\Module\Eagle\Common\HostLinks;
use Icinga\Module\Eagle\Common\HostStates;
use Icinga\Module\Eagle\Common\Icons;
use Icinga\Module\Eagle\Common\Links;
use Icinga\Module\Eagle\Common\ServiceLinks;
use Icinga\Module\Eagle\Common\ServiceStates;
use Icinga\Module\Eagle\Model\Host;
use Icinga\Module\Eagle\Widget\DowntimeList;
use Icinga\Module\Eagle\Widget\HorizontalKeyValue;
use Icinga\Module\Eagle\Widget\ItemList\CommentList;
use Icinga\Module\Eagle\Widget\ShowMore;
use Icinga\Module\Eagle\Widget\TagList;
use Icinga\Module\Monitoring\Plugin\PerfdataSet;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Perfdata;

class ObjectDetail extends BaseHtmlElement
{
    protected $object;

    protected $objectType;

    protected $defaultAttributes = ['class' => 'host-detail'];

    protected $tag = 'div';

    public function __construct($object)
    {
        $this->object = $object;
        $this->objectType = $object instanceof Host ? 'host' : 'service';
    }

    protected function createComments()
    {
        if ($this->objectType === 'host') {
            $link = HostLinks::comments($this->object);
        } else {
            $link = ServiceLinks::comments($this->object, $this->object->host);
        }

        $comments = $this->object->comment->limit(3)->peekAhead()->execute();

        return [
            Html::tag('h2', 'Comments'),
            new CommentList($comments),
            new ShowMore($comments, $link)
        ];
    }

    protected function createCustomVars()
    {
        return [
            Html::tag('h2', 'Custom Variables'),
            new CustomVarTable($this->object->customvar)
        ];
    }

    protected function createDowntimes()
    {
        if ($this->objectType === 'host') {
            $link = HostLinks::downtimes($this->object);
        } else {
            $link = ServiceLinks::downtimes($this->object, $this->object->host);
        }

        $downtimes = $this->object->downtime->limit(3)->peekAhead()->execute();

        return [
            Html::tag('h2', 'Downtimes'),
            new DowntimeList($this->object->downtime),
            new ShowMore($downtimes, $link)
        ];
    }

    protected function createEvents()
    {
        if ($this->objectType === 'host') {
            $state = HostStates::text($this->object->state->soft_state);
        } else {
            $state = ServiceStates::text($this->object->state->soft_state);
        }
        return [
            Html::tag('h2', 'Plugin Output'),
            (new EventBox())
                ->setCaption($this->object->state->output . "\n" . $this->object->state->long_output, true)
                ->setState($state)
        ];
    }

    protected function createGroups()
    {
        $groups = [Html::tag('h2', 'Groups')];

        if ($this->objectType === 'host') {
            $hostgroupList = new TagList();

            foreach ($this->object->hostgroup as $hostgroup) {
                $hostgroupList->addLink($hostgroup->display_name, Links::hostgroup($hostgroup));
            }

            $groups[] = new HorizontalKeyValue('Host Groups', $hostgroupList);
        } else {
            $servicegroupList = new TagList();

            foreach ($this->object->servicegroup as $servicegroup) {
                $servicegroupList->addLink($servicegroup->display_name, Links::servicegroup($servicegroup));
            }

            $groups[] = new HorizontalKeyValue('Service Groups', $servicegroupList);
        }

        return $groups;
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
            new HorizontalKeyValue('Users', $userList),
            new HorizontalKeyValue('User Groups', $usergroupList)
        ];
    }

    protected function createPerformanceData()
    {
        return [
            Html::tag('h2', 'Performance Data'),
            new Perfdata(
                PerfdataSet::fromString($this->object->state->performance_data)->asArray(), $this->object->checkcommand
            )
        ];
    }

    protected function getUsersAndUsergroups()
    {
        $users = [];
        $usergroups = [];

        foreach ($this->object->notification as $notification) {
            foreach ($notification->user as $user) {
                $users[$user->name] = $user;
            }

            foreach ($notification->usergroup as $usergroup) {
                $usergroups[$usergroup->name] = $usergroup;
            }
        }

        return [$users, $usergroups];
    }

    protected function assemble()
    {
        $this->add([
            $this->createEvents(),
            $this->createGroups(),
            $this->createComments(),
            $this->createDowntimes(),
            $this->createNotifications(),
            $this->createPerformanceData(),
            $this->createCustomVars()
        ]);
    }
}
