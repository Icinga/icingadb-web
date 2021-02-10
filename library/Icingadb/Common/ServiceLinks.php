<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Web\Url;

abstract class ServiceLinks
{
    public static function acknowledge(Service $service, Host $host)
    {
        return Url::fromPath(
            'icingadb/service/acknowledge',
            ['name' => $service->name, 'host.name' => $host->name]
        );
    }

    public static function addComment(Service $service, Host $host)
    {
        return Url::fromPath(
            'icingadb/service/add-comment',
            ['name' => $service->name, 'host.name' => $host->name]
        );
    }

    public static function checkNow(Service $service, Host $host)
    {
        return Url::fromPath(
            'icingadb/service/check-now',
            ['name' => $service->name, 'host.name' => $host->name]
        );
    }

    public static function scheduleCheck(Service $service, Host $host)
    {
        return Url::fromPath(
            'icingadb/service/schedule-check',
            ['name' => $service->name, 'host.name' => $host->name]
        );
    }

    public static function cancelDowntime(Service $service, Host $host)
    {
        return Url::fromPath(
            'icingadb/service/delete-downtime',
            ['name' => $service->name, 'host.name' => $host->name]
        );
    }

    public static function comments(Service $service, Host $host)
    {
        return Url::fromPath(
            'icingadb/service/comments',
            ['name' => $service->name, 'host.name' => $host->name]
        );
    }

    public static function downtimes(Service $service, Host $host)
    {
        return Url::fromPath(
            'icingadb/service/downtimes',
            ['name' => $service->name, 'host.name' => $host->name]
        );
    }

    public static function history(Service $service, Host $host)
    {
        return Url::fromPath(
            'icingadb/service/history',
            ['name' => $service->name, 'host.name' => $host->name]
        );
    }

    public static function removeAcknowledgement(Service $service, Host $host)
    {
        return Url::fromPath(
            'icingadb/service/remove-acknowledgement',
            ['name' => $service->name, 'host.name' => $host->name]
        );
    }

    public static function scheduleDowntime(Service $service, Host $host)
    {
        return Url::fromPath(
            'icingadb/service/schedule-downtime',
            ['name' => $service->name, 'host.name' => $host->name]
        );
    }

    public static function sendCustomNotification(Service $service, Host $host)
    {
        return Url::fromPath(
            'icingadb/service/send-custom-notification',
            ['name' => $service->name, 'host.name' => $host->name]
        );
    }

    public static function processCheckresult(Service $service, Host $host)
    {
        return Url::fromPath(
            'icingadb/service/process-checkresult',
            ['name' => $service->name, 'host.name' => $host->name]
        );
    }

    public static function toggleFeatures(Service $service, Host $host)
    {
        return Url::fromPath(
            'icingadb/service/toggle-features',
            ['name' => $service->name, 'host.name' => $host->name]
        );
    }
}
