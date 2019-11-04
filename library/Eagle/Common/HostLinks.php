<?php

namespace Icinga\Module\Eagle\Common;

use Icinga\Module\Eagle\Model\Host;
use ipl\Web\Url;

abstract class HostLinks
{
    public static function acknowledge(Host $host)
    {
        return Url::fromPath('eagle/host/acknowledge', ['name' => $host->name]);
    }

    public static function addComment(Host $host)
    {
        return Url::fromPath('eagle/host/add-comment', ['name' => $host->name]);
    }

    public static function checkNow(Host $host)
    {
        return Url::fromPath('eagle/host/check-now', ['name' => $host->name]);
    }

    public static function comments(Host $host)
    {
        return Url::fromPath('eagle/host/comments', ['name' => $host->name]);
    }

    public static function downtimes(Host $host)
    {
        return Url::fromPath('eagle/host/downtimes', ['name' => $host->name]);
    }

    public static function history(Host $host)
    {
        return Url::fromPath('eagle/host/history', ['name' => $host->name]);
    }

    public static function removeAcknowledgement(Host $host)
    {
        return Url::fromPath('eagle/host/remove-acknowledgement', ['name' => $host->name]);
    }

    public static function removeComment(Host $host)
    {
        return Url::fromPath('eagle/host/delete-comment', ['name' => $host->name]);
    }

    public static function scheduleDowntime(Host $host)
    {
        return Url::fromPath('eagle/host/schedule-downtime', ['name' => $host->name]);
    }

    public static function sendCustomNotification(Host $host)
    {
        return Url::fromPath('eagle/host/send-custom-notification', ['name' => $host->name]);
    }

    public static function services(Host $host)
    {
        return Url::fromPath('eagle/host/services', ['name' => $host->name]);
    }
}
