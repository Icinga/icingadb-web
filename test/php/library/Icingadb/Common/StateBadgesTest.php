<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Tests\Icinga\Modules\Icingadb\Common;

use Icinga\Module\Icingadb\Common\StateBadges;
use Icinga\Web\UrlParams;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;
use ipl\Web\Widget\Link;
use PHPUnit\Framework\TestCase;

class StateBadgesTest extends TestCase
{
    public function testCreateLinkRendersBaseFilterCorrectly()
    {
        $stateBadges = $this->createStateBadges()
            ->setBaseFilter(Filter::any(
                Filter::equal('foo', 'bar'),
                Filter::equal('bar', 'foo')
            ));

        $link = $stateBadges->generateLink('test', Filter::equal('rab', 'oof'));

        $this->assertSame(
            'rab=oof&(foo=bar|bar=foo)',
            $link->getUrl()->getQueryString()
        );
    }

    private function createStateBadges()
    {
        $queryString = null;

        $urlMock = $this->createConfiguredMock(Url::class, [
            'getBasePath' => 'test',
            'getParams' => $this->createConfiguredMock(UrlParams::class, [
                'toArray' => []
            ])
        ]);
        $urlMock->method('setFilter')->willReturnCallback(
            function ($qs) use ($urlMock, &$queryString) {
                $queryString = QueryString::render($qs);

                return $urlMock;
            }
        );
        $urlMock->method('getQueryString')->willReturnCallback(
            function () use (&$queryString) {
                return $queryString;
            }
        );

        return new class ($urlMock) extends StateBadges {
            private $urlMock;

            public function __construct($urlMock)
            {
                $this->urlMock = $urlMock;

                parent::__construct((object) []);
            }

            protected function getBaseUrl(): Url
            {
                return $this->urlMock;
            }

            protected function getType(): string
            {
                return 'test';
            }

            protected function getPrefix(): string
            {
                return 'Test';
            }

            protected function getStateInt(string $state): int
            {
                return 0;
            }

            public function generateLink($content, Filter\Rule $filter = null): Link
            {
                return parent::createLink($content, $filter);
            }
        };
    }
}
