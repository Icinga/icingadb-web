<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Module\Icingadb\Forms;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Config;
use Icinga\Module\Icingadb\Forms\GeneralConfigForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class GeneralConfigFormTest extends TestCase
{
    public static function icingaweb2UrlProvider(): array
    {
        return [
            'default port' => [
                'http://example.com/icingaweb2',
                'http://example.com/icingaweb2/icingadb/config/general-settings',
                '/icingaweb2/index.php',
                []
            ],
            'non-default port without proxy' => [
                'http://example.com:8080/icingaweb2',
                'http://example.com:8080/icingaweb2/icingadb/config/general-settings',
                '/icingaweb2/index.php',
                []
            ],
            'https without proxy' => [
                'https://example.com/icingaweb2',
                'https://example.com/icingaweb2/icingadb/config/general-settings',
                '/icingaweb2/index.php',
                []
            ],
            'installed at document root' => [
                'http://example.com',
                'http://example.com/icingadb/config/general-settings',
                '/index.php',
                []
            ],
            'forwarded protocol with passed through host' => [
                'https://example.com/icingaweb2',
                'http://example.com/icingaweb2/icingadb/config/general-settings',
                '/icingaweb2/index.php',
                ['X-Forwarded-Proto' => 'https']
            ],
            'forwarded host without port' => [
                'https://example.com/icingaweb2',
                'http://icingaweb:8080/icingaweb2/icingadb/config/general-settings',
                '/icingaweb2/index.php',
                ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'example.com']
            ],
            'forwarded non-default port' => [
                'https://example.com:8443/icingaweb2',
                'http://icingaweb:8080/icingaweb2/icingadb/config/general-settings',
                '/icingaweb2/index.php',
                ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'example.com', 'X-Forwarded-Port' => '8443']
            ],
            'forwarded default port' => [
                'https://example.com/icingaweb2',
                'http://icingaweb:8080/icingaweb2/icingadb/config/general-settings',
                '/icingaweb2/index.php',
                ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'example.com', 'X-Forwarded-Port' => '443']
            ],
            'forwarded port already part of forwarded host' => [
                'https://example.com:8443/icingaweb2',
                'http://icingaweb:8080/icingaweb2/icingadb/config/general-settings',
                '/icingaweb2/index.php',
                [
                    'X-Forwarded-Proto' => 'https',
                    'X-Forwarded-Host'  => 'example.com:8443',
                    'X-Forwarded-Port'  => '8443'
                ]
            ],
            'proxy chain' => [
                'https://example.com/icingaweb2',
                'http://icingaweb:8080/icingaweb2/icingadb/config/general-settings',
                '/icingaweb2/index.php',
                ['X-Forwarded-Proto' => 'https, http', 'X-Forwarded-Host' => 'example.com, proxy.internal']
            ],
            'forwarded prefix' => [
                'https://example.com/icingaweb2',
                'http://icingaweb:8080/icingadb/config/general-settings',
                '/index.php',
                [
                    'X-Forwarded-Proto'  => 'https',
                    'X-Forwarded-Host'   => 'example.com',
                    'X-Forwarded-Prefix' => '/icingaweb2'
                ]
            ],
            'forwarded prefix with trailing slash' => [
                'https://example.com/icingaweb2',
                'http://icingaweb:8080/icingadb/config/general-settings',
                '/index.php',
                [
                    'X-Forwarded-Proto'  => 'https',
                    'X-Forwarded-Host'   => 'example.com',
                    'X-Forwarded-Prefix' => '/icingaweb2/'
                ]
            ]
        ];
    }

    #[DataProvider('icingaweb2UrlProvider')]
    public function testDetectIcingaweb2Url(
        string $expected,
        string $requestUrl,
        string $scriptName,
        array $headers
    ): void {
        $serverParams = [
            'REMOTE_ADDR'     => '10.0.0.1',
            'SCRIPT_NAME'     => $scriptName,
            'SCRIPT_FILENAME' => '/usr/share/icingaweb2/public/index.php',
            'REQUEST_URI'     => parse_url($requestUrl, PHP_URL_PATH),
            'HTTPS'           => parse_url($requestUrl, PHP_URL_SCHEME) === 'https' ? 'on' : 'off'
        ];

        $form = (new GeneralConfigForm(new Config()))
            ->setRequest(new ServerRequest('POST', $requestUrl, $headers, null, '1.1', $serverParams));

        $this->assertSame($expected, (new ReflectionMethod($form, 'detectIcingaweb2Url'))->invoke($form));
    }
}
