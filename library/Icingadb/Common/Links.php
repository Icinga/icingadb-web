<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Module\Icingadb\Model\Comment;
use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\NotificationHistory;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Model\Usergroup;
use ipl\Web\Url;

abstract class Links
{
    public static function comment(Comment $comment): Url
    {
        return Url::fromPath('icingadb/comment', ['name' => $comment->name]);
    }

    public static function comments(): Url
    {
        return Url::fromPath('icingadb/comments');
    }

    public static function commentsDelete(): Url
    {
        return Url::fromPath('icingadb/comments/delete');
    }

    public static function commentsDetails(): Url
    {
        return Url::fromPath('icingadb/comments/details');
    }

    public static function downtime(Downtime $downtime): Url
    {
        return Url::fromPath('icingadb/downtime', ['name' => $downtime->name]);
    }

    public static function downtimes(): Url
    {
        return Url::fromPath('icingadb/downtimes');
    }

    public static function downtimesDelete(): Url
    {
        return Url::fromPath('icingadb/downtimes/delete');
    }

    public static function downtimesDetails(): Url
    {
        return Url::fromPath('icingadb/downtimes/details');
    }

    public static function host(Host $host): Url
    {
        return Url::fromPath('icingadb/host', ['name' => $host->name]);
    }

    public static function hostSource(Host $host): Url
    {
        return Url::fromPath('icingadb/host/source', ['name' => $host->name]);
    }

    public static function hostsDetails(): Url
    {
        return Url::fromPath('icingadb/hosts/details');
    }

    public static function hostgroup($hostgroup): Url
    {
        return Url::fromPath('icingadb/hostgroup', ['name' => $hostgroup->name]);
    }

    public static function hosts(): Url
    {
        return Url::fromPath('icingadb/hosts');
    }

    public static function service(Service $service, Host $host): Url
    {
        return Url::fromPath('icingadb/service', ['name' => $service->name, 'host.name' => $host->name]);
    }

    public static function serviceSource(Service $service, Host $host): Url
    {
        return Url::fromPath('icingadb/service/source', ['name' => $service->name, 'host.name' => $host->name]);
    }

    public static function servicesDetails(): Url
    {
        return Url::fromPath('icingadb/services/details');
    }

    public static function servicegroup($servicegroup): Url
    {
        return Url::fromPath('icingadb/servicegroup', ['name' => $servicegroup->name]);
    }

    public static function services(): Url
    {
        return Url::fromPath('icingadb/services');
    }

    public static function toggleHostsFeatures(): Url
    {
        return Url::fromPath('icingadb/hosts/toggle-features');
    }

    public static function toggleServicesFeatures(): Url
    {
        return Url::fromPath('icingadb/services/toggle-features');
    }

    public static function user(User $user): Url
    {
        return Url::fromPath('icingadb/contact', ['name' => $user->name]);
    }

    public static function usergroup(Usergroup $usergroup): Url
    {
        return Url::fromPath('icingadb/usergroup', ['name' => $usergroup->name]);
    }

    public static function users(): Url
    {
        return Url::fromPath('icingadb/contacts');
    }

    public static function usergroups(): Url
    {
        return Url::fromPath('icingadb/usergroups');
    }

    public static function event(History $event): Url
    {
        return Url::fromPath('icingadb/event', ['id' => bin2hex($event->id)]);
    }
}
