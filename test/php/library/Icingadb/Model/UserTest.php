<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Modules\Icingadb\Model;

use Icinga\Module\Icingadb\Model\User;
use ipl\Sql\Test\TestConnection;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testNotificationFlagIsHydratedAsBoolean(): void
    {
        $hydrator = User::on(new TestConnection())->createHydrator();

        $disabled = $hydrator->hydrate(['notifications_enabled' => 'n'], new User());
        $enabled = $hydrator->hydrate(['notifications_enabled' => 'y'], new User());

        $this->assertFalse($disabled->notifications_enabled);
        $this->assertTrue($enabled->notifications_enabled);
    }
}
