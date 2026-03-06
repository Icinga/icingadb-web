<?php

// SPDX-FileCopyrightText: 2025 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
