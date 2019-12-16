<?php

namespace Icinga\Module\Icingadb\Common;

use Icinga\Module\Icingadb\Model\Comment;
use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Hostgroup;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\Servicegroup;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Model\Usergroup;
use ipl\Web\Url;

abstract class Links
{
    public static function comment(Comment $comment)
    {
        return Url::fromPath('icingadb/comment', ['name' => $comment->name]);
    }

    public static function comments()
    {
        return Url::fromPath('icingadb/comments');
    }

    public static function commentsDetails()
    {
        return Url::fromPath('icingadb/comments/details');
    }

    public static function downtime(Downtime $downtime)
    {
        return Url::fromPath('icingadb/downtime', ['name' => $downtime->name]);
    }

    public static function downtimes()
    {
        return Url::fromPath('icingadb/downtimes');
    }

    public static function downtimesDetails()
    {
        return Url::fromPath('icingadb/downtimes/details');
    }

    public static function host(Host $host)
    {
        return Url::fromPath('icingadb/host', ['name' => $host->name]);
    }

    public static function hostsDetails()
    {
        return Url::fromPath('icingadb/hosts/details');
    }

    public static function hostgroup($hostgroup)
    {
        return Url::fromPath('icingadb/hostgroup', ['name' => $hostgroup->name]);
    }

    public static function hosts()
    {
        return Url::fromPath('icingadb/hosts');
    }

    public static function service(Service $service, Host $host)
    {
        return Url::fromPath('icingadb/service', ['name' => $service->name, 'host.name' => $host->name]);
    }

    public static function servicesDetails()
    {
        return Url::fromPath('icingadb/services/details');
    }

    public static function servicegroup($servicegroup)
    {
        return Url::fromPath('icingadb/servicegroup', ['name' => $servicegroup->name]);
    }

    public static function services()
    {
        return Url::fromPath('icingadb/services');
    }

    public static function user(User $user)
    {
        return Url::fromPath('icingadb/user', ['name' => $user->name]);
    }

    public static function usergroup(Usergroup $usergroup)
    {
        return Url::fromPath('icingadb/usergroup', ['name' => $usergroup->name]);
    }
}
