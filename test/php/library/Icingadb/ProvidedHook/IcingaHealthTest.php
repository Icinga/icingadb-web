<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Tests\Icinga\Modules\Icingadb\ProvidedHook;

use Icinga\Module\Icingadb\ProvidedHook\IcingaHealth;
use PHPUnit\Framework\TestCase;

class IcingaHealthTest extends TestCase
{
    public function testNormalizeVersion()
    {
        $this->assertEquals('1.4.0', IcingaHealth::normalizeVersion('1.4.0'));
        $this->assertEquals('1.4.0', IcingaHealth::normalizeVersion('1.4.0-1d5a35da5'));
        $this->assertEquals('1.4.0', IcingaHealth::normalizeVersion('v1.4.0'));
        $this->assertEquals('1.4.0', IcingaHealth::normalizeVersion('v1.4.0-1d5a35da5'));
    }
}
