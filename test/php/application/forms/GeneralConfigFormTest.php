<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Module\Icingadb\Forms;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Icinga\Module\Icingadb\Lib\GeneralConfigForm;

class GeneralConfigFormTest extends TestCase
{
    public static function icingaweb2UrlProvider(): array
    {
        return [
            'default port' => [
                'http://example.com/icingaweb2',
                'http://example.com/icingaweb2/icingadb/config/general-settings',
                []
            ],
            'non-default port without proxy' => [
                'http://example.com:8080/icingaweb2',
                'http://example.com:8080/icingaweb2/icingadb/config/general-settings',
                []
            ],
            'forwarded protocol with passed through host' => [
                'https://example.com/icingaweb2',
                'http://example.com/icingaweb2/icingadb/config/general-settings',
                ['X-Forwarded-Proto' => 'https']
            ],
            'forwarded host without port' => [
                'https://example.com/icingaweb2',
                'http://icingaweb:8080/icingaweb2/icingadb/config/general-settings',
                ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'example.com']
            ],
            'forwarded non-default port' => [
                'https://example.com:8443/icingaweb2',
                'http://icingaweb:8080/icingaweb2/icingadb/config/general-settings',
                ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'example.com', 'X-Forwarded-Port' => '8443']
            ],
            'forwarded default port' => [
                'https://example.com/icingaweb2',
                'http://icingaweb:8080/icingaweb2/icingadb/config/general-settings',
                ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'example.com', 'X-Forwarded-Port' => '443']
            ],
            'forwarded port already part of forwarded host' => [
                'https://example.com:8443/icingaweb2',
                'http://icingaweb:8080/icingaweb2/icingadb/config/general-settings',
                [
                    'X-Forwarded-Proto' => 'https',
                    'X-Forwarded-Host'  => 'example.com:8443',
                    'X-Forwarded-Port'  => '8443'
                ]
            ],
            'proxy chain' => [
                'https://example.com/icingaweb2',
                'http://icingaweb:8080/icingaweb2/icingadb/config/general-settings',
                ['X-Forwarded-Proto' => 'https, http', 'X-Forwarded-Host' => 'example.com, proxy.internal']
            ]
        ];
    }

    #[DataProvider('icingaweb2UrlProvider')]
    public function testDetectIcingaweb2Url(string $expected, string $requestUrl, array $headers): void
    {
        $form = (new GeneralConfigForm(new Config()))
            ->setRequest(new ServerRequest('POST', $requestUrl, $headers));

        $this->assertSame($expected, $form->detectIcingaweb2Url());
    }
}
