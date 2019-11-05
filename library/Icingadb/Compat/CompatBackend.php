<?php

namespace Icinga\Module\Eagle\Compat;

use Icinga\Module\Monitoring\Backend\MonitoringBackend;

/**
 * Class CompatBackend
 * @package Icinga\Module\Eagle\Compat
 */
class CompatBackend extends MonitoringBackend
{
    /**
     * @param string $name Ignored
     * @param mixed $config Ignored
     */
    public function __construct()
    {

    }

    /**
     * @param string $programVersion Ignored
     * @return true
     */
    public function isIcinga2($_ = null)
    {
        return true;
    }

    /**
     * @return string "2.12", hardcoded
     */
    public function getProgramVersion()
    {
        return '2.12';
    }
}
