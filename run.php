<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

/** @var $this \Icinga\Application\Modules\Module */

$this->provideHook('ApplicationState');
$this->provideHook('X509/Sni');
$this->provideHook('health', 'IcingaHealth');
$this->provideHook('health', 'RedisHealth');
$this->provideHook('Reporting/Report', 'Reporting/HostSlaReport');
$this->provideHook('Reporting/Report', 'Reporting/ServiceSlaReport');

if ($this::exists('reporting')) {
    $this->provideHook('Icingadb/HostsDetailExtension', 'CreateHostSlaReport');
    $this->provideHook('Icingadb/ServicesDetailExtension', 'CreateServiceSlaReport');
}

if (! $this::exists('monitoring')) {
    $modulePath = null;
    foreach ($this->app->getModuleManager()->getModuleDirs() as $path) {
        $pathToTest = join(DIRECTORY_SEPARATOR, [$path, 'monitoring']);
        if (file_exists($pathToTest)) {
            $modulePath = $pathToTest;
            break;
        }
    }

    if ($modulePath === null) {
        Icinga\Application\Logger::error('Unable to locate monitoring module');
    } else {
        // Ensure we can load some classes/interfaces for compatibility with legacy hooks
        $this->app->getLoader()->registerNamespace(
            'Icinga\\Module\\Monitoring',
            join(DIRECTORY_SEPARATOR, [$modulePath, 'library', 'Monitoring']),
            join(DIRECTORY_SEPARATOR, [$modulePath, 'application'])
        );
    }
}
