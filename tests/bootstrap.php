<?php

use Icinga\Application\Cli;

// TODO(el): Autoloading this file does not work:
require_once __DIR__ . '/TestCase.php';
// TODO(el): Meant to be used with our dev docker env:
require_once '/icingaweb2/library/Icinga/Application/Cli.php';

if (array_key_exists('ICINGAWEB_CONFIGDIR', $_SERVER)) {
    $configDir = $_SERVER['ICINGAWEB_CONFIGDIR'];
} else {
    $configDir = '/etc/icingaweb2';
}

Cli::start(__DIR__, $configDir)
    ->getModuleManager()
    ->loadModule('icingadb', dirname(__DIR__));
