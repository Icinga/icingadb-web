<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Modules\Icingadb\Notifications;

use Icinga\Module\Icingadb\Notifications\ManagesIncidents;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Compares ManagesIncidents::pack() and ManagesIncidents::deriveDeterministicEventUUID()
 * with Icinga DB's DeriveDeterministicEventUUID()
 */
class ManagesIncidentsTest extends TestCase
{
    private const ENVIRONMENT_ID = '8ab3b8cbb1e1f0f8d1f6a62b1a3e3b7d5d8f1b0c';

    private const TIMESTAMP = 1760534400123;

    private const GO_PACKED_HOST = '05000000000000000404000000000000002838616233623863626231653166306638643166366136326'
        . '23161336533623764356438663162306304000000000000000761636b5f73657404000000000000000c6578616d706c652d686f73740'
        . '342799e8070c7b000';

    private const GO_PACKED_SERVICE = '05000000000000000404000000000000002838616233623863626231653166306638643166366136'
        . '32623161336533623764356438663162306304000000000000000961636b5f636c6561720400000000000000126578616d706c652d68'
        . '6f73742170696e67340342799e8070c7b000';

    private const GO_UUID_HOST = '12a0a782-21d5-5cdb-a2a8-3e7b47d3a294';

    private const GO_UUID_SERVICE = 'c344c752-5cde-5c49-b6a5-9333b33a36da';

    public function testPackMatchesGoForHost(): void
    {
        $packed = $this->callTrait('pack', 'ack_set', $this->createHost());

        $this->assertSame(self::GO_PACKED_HOST, bin2hex($packed));
    }

    public function testPackMatchesGoForService(): void
    {
        $packed = $this->callTrait('pack', 'ack_clear', $this->createService());

        $this->assertSame(self::GO_PACKED_SERVICE, bin2hex($packed));
    }

    public function testDeriveDeterministicEventUUIDMatchesGoForHost(): void
    {
        $uuid = $this->callTrait('deriveDeterministicEventUUID', 'ack_set', $this->createHost());

        $this->assertSame(self::GO_UUID_HOST, $uuid->toString());
    }

    public function testDeriveDeterministicEventUUIDMatchesGoForService(): void
    {
        $uuid = $this->callTrait('deriveDeterministicEventUUID', 'ack_clear', $this->createService());

        $this->assertSame(self::GO_UUID_SERVICE, $uuid->toString());
    }

    private function createHost(): array
    {
        return [
            'host' => 'example-host',
            'environment' => self::ENVIRONMENT_ID
        ];
    }

    private function createService(): array
    {
        return [
            'host' => 'example-host',
            'environment' => self::ENVIRONMENT_ID,
            'service' => 'ping4'
        ];
    }

    /**
     * Call the given private method of ManagesIncidents with the test timestamp
     */
    private function callTrait(string $method, string $eventType, array $tags): mixed
    {
        $subject = new class {
            use ManagesIncidents;
        };

        return (new ReflectionMethod($subject, $method))->invoke($subject, $eventType, $tags, self::TIMESTAMP);
    }
}
