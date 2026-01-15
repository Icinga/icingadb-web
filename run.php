<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

/** @var $this \Icinga\Application\Modules\Module */

$this->provideHook('ApplicationState');
$this->provideHook('X509/Sni');
$this->provideHook('health', 'IcingaHealth');
$this->provideHook('health', 'RedisHealth');
$this->provideHook('Reporting/Report', 'Reporting/HostSlaReport');
$this->provideHook('Reporting/Report', 'Reporting/TotalHostSlaReport');
$this->provideHook('Reporting/Report', 'Reporting/ServiceSlaReport');
$this->provideHook('Reporting/Report', 'Reporting/TotalServiceSlaReport');

if ($this::exists('notifications')) {
    $this->provideHook('Notifications/v1/Source');
}

if ($this::exists('reporting')) {
    $this->provideHook('Icingadb/HostActions', 'CreateHostSlaReport');
    $this->provideHook('Icingadb/ServiceActions', 'CreateServiceSlaReport');
    $this->provideHook('Icingadb/HostsDetailExtension', 'CreateHostsSlaReport');
    $this->provideHook('Icingadb/ServicesDetailExtension', 'CreateServicesSlaReport');
}

if (! $this::exists('monitoring') && $this->app->getModuleManager()->hasInstalled('monitoring')) {
    // For compatibility reasons, Icinga DB Web also supports hooks originally written for the monitoring module.
    // This requires the monitoring module to be either enabled or installed.
    // If it is only installed, its autoloader must be registered manually to resolve monitoring module hook classes.
    $this->app->getModuleManager()->getModule('monitoring', assertLoaded: false)->registerAutoloader();

    $this->addRoute(
        'icingadb-history-timestamp-preference',
        new Zend_Controller_Router_Route(
            'icingadb/history/timestamp-preference',
            [
                'module'     => 'icingadb',
                'controller' => 'history',
                'action'     => 'timeStamp'
            ]
        )
    );
}
