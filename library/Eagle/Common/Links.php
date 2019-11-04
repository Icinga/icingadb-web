<?php

namespace Icinga\Module\Eagle\Common;

use Icinga\Module\Eagle\Model\Comment;
use Icinga\Module\Eagle\Model\Host;
use Icinga\Module\Eagle\Model\Hostgroup;
use Icinga\Module\Eagle\Model\Service;
use Icinga\Module\Eagle\Model\Servicegroup;
use Icinga\Module\Eagle\Model\User;
use Icinga\Module\Eagle\Model\Usergroup;
use ipl\Web\Url;

abstract class Links
{
    public static function comment(Comment $comment)
    {
        return Url::fromPath('eagle/comment', ['name' => $comment->name]);
    }

    public static function comments()
    {
        return Url::fromPath('eagle/comments');
    }

    public static function host(Host $host)
    {
        return Url::fromPath('eagle/host', ['name' => $host->name]);
    }

    public static function hostgroup(Hostgroup $hostgroup)
    {
        return Url::fromPath('eagle/hostgroup', ['name' => $hostgroup->name]);
    }

    public static function service(Service $service, Host $host)
    {
        return Url::fromPath('eagle/service', ['name' => $service->name, 'host.name' => $host->name]);
    }

    public static function servicegroup(Servicegroup $servicegroup)
    {
        return Url::fromPath('eagle/servicegroup', ['name' => $servicegroup->name]);
    }

    public static function user(User $user)
    {
        return Url::fromPath('eagle/user', ['name' => $user->name]);
    }

    public static function usergroup(Usergroup $usergroup)
    {
        return Url::fromPath('eagle/usergroup', ['name' => $usergroup->name]);
    }
}
