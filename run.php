<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

/** @var $this \Icinga\Application\Modules\Module */

$this->provideHook('ApplicationState');
$this->provideHook('X509/Sni');
$this->provideHook('health', 'IcingaHealth');
$this->provideHook('health', 'RedisHealth');

if (! $this->app->getModuleManager()->hasEnabled('monitoring')) {
    // Ensure we can load some classes/interfaces for compatibility with legacy hooks
    $this->app->getLoader()->registerNamespace(
        'Icinga\\Module\\Monitoring',
        $this->getLibDir() . '/Monitoring',
        $this->getApplicationDir()
    );
}
