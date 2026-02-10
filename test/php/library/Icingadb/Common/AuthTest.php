<?php

namespace Tests\Icinga\Modules\Icingadb\Common;

use ipl\Stdlib\Filter;
use ipl\Web\Filter\Renderer;
use PHPUnit\Framework\TestCase;
use Icinga\Module\Icingadb\Common\Auth;

class AuthTest extends TestCase
{
    use Auth;

    public function testFlatteningOfNestedAnyRules(): void
    {
        $to = Filter::any();
        $from = Filter::any(
            Filter::equal('a', 1),
            Filter::equal('b', 2),
            Filter::any(
                Filter::equal('c', 3),
                Filter::equal('d', 4),
                Filter::any(
                    Filter::equal('e', 5),
                    Filter::equal('f', 6)
                )
            )
        );

        $this->flattenSemanticallyEqualRules($to, $from);

        $this->assertFilterEquals(
            '(a=1|b=2|c=3|d=4|e=5|f=6)',
            $to
        );
    }

    public function testFlatteningOfNestedAllRules(): void
    {
        $to = Filter::all();
        $from = Filter::all(
            Filter::equal('a', 1),
            Filter::equal('b', 2),
            Filter::all(
                Filter::equal('c', 3),
                Filter::equal('d', 4),
                Filter::all(
                    Filter::equal('e', 5),
                    Filter::equal('f', 6)
                )
            )
        );

        $this->flattenSemanticallyEqualRules($to, $from);

        $this->assertFilterEquals(
            '(a=1&b=2&c=3&d=4&e=5&f=6)',
            $to
        );
    }

    public function testFlatteningOfNestedNoneRules(): void
    {
        $to = Filter::none();
        $from = Filter::none(
            Filter::equal('a', 1),
            Filter::equal('b', 2),
            Filter::none(
                Filter::equal('c', 3),
                Filter::equal('d', 4),
                Filter::none(
                    Filter::equal('e', 5),
                    Filter::equal('f', 6)
                )
            )
        );

        $this->flattenSemanticallyEqualRules($to, $from);

        $this->assertFilterEquals(
            '!(a=1|b=2|c=3|d=4|e=5|f=6)',
            $to
        );
    }

    public function testFlatteningOfNestedMixedRules(): void
    {
        $to = Filter::any();
        $from = Filter::any(
            Filter::equal('a', 1),
            Filter::all(
                Filter::equal('b', 2),
                Filter::equal('c', 3)
            ),
            Filter::none(
                Filter::equal('d', 4),
                Filter::equal('e', 5)
            ),
            Filter::any(
                Filter::equal('f', 6),
                Filter::equal('g', 7),
                Filter::none(
                    Filter::none(
                        Filter::equal('h', 8),
                        Filter::equal('i', 9)
                    )
                )
            )
        );

        $this->flattenSemanticallyEqualRules($to, $from);

        $this->assertFilterEquals(
            '(a=1|(b=2&c=3)|!(d=4|e=5)|f=6|g=7|!(h=8|i=9))',
            $to
        );
    }

    public function testFlatteningOfEdgeCases(): void
    {
        $to = Filter::any();
        $from = Filter::any(
            Filter::all(
                Filter::equal('a', 1)
            ),
            Filter::none(
                Filter::equal('b', 2)
            ),
            Filter::any(
                Filter::equal('c', 3)
            ),
            Filter::equal('d', 4)
        );

        $this->flattenSemanticallyEqualRules($to, $from);

        $this->assertFilterEquals(
            '(a=1|!(b=2)|c=3|d=4)',
            $to
        );
    }

    public function testFlatteningOfSemanticallyUnequalRules(): void
    {
        $to = Filter::any();
        $from = Filter::all(
            Filter::equal('a', 1),
            Filter::equal('b', 2),
            Filter::any(
                Filter::equal('c', 3),
                Filter::equal('d', 4),
                Filter::any(
                    Filter::equal('e', 5),
                    Filter::equal('f', 6)
                )
            ),
            Filter::all(
                Filter::equal('g', 7),
            )
        );

        $this->flattenSemanticallyEqualRules($to, $from);

        $this->assertFilterEquals(
            '((a=1&b=2&(c=3|d=4|e=5|f=6)&g=7)|)',
            $to
        );

        $to = Filter::all();
        $from = Filter::any(
            Filter::equal('a', 1),
            Filter::all(
                Filter::equal('b', 2),
                Filter::equal('c', 3)
            ),
            Filter::none(
                Filter::equal('d', 4),
                Filter::equal('e', 5)
            ),
            Filter::any(
                Filter::equal('f', 6),
                Filter::equal('g', 7)
            )
        );

        $this->flattenSemanticallyEqualRules($to, $from);

        $this->assertFilterEquals(
            '((a=1|(b=2&c=3)|!(d=4|e=5)|f=6|g=7))',
            $to
        );
    }

    private function assertFilterEquals(string $expected, Filter\Rule $actual): void
    {
        $this->assertEquals(
            $expected,
            (new Renderer($actual))->setStrict()->render()
        );
    }
}
