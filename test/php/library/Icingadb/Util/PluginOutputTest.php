<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Tests\Icinga\Module\Icingadb\Util;

use Icinga\Module\Icingadb\Util\PluginOutput;
use PHPUnit\Framework\TestCase;

class PluginOutputTest extends TestCase
{
    public function testRenderPlainText()
    {
        $input = 'This is a plain text';
        $expectedOutput = $input;

        $this->assertSame(
            $expectedOutput,
            (new PluginOutput($input))->render(),
            'PluginOutput::render does not return expected values'
        );
    }

    public function testRenderTextWithStates()
    {
        $input = <<<'INPUT'
[OK] Dummy state
    \_ [OK] Fake "state"
    \_ [WARNING] Fake state again
INPUT;

        $expectedOutput = <<<'EXPECTED_OUTPUT'
<span class="state-ball ball-size-m state-ok"></span> Dummy state
    \_ <span class="state-ball ball-size-m state-ok"></span> Fake &quot;state&quot;
    \_ <span class="state-ball ball-size-m state-warning"></span> Fake state again
EXPECTED_OUTPUT;

        $this->assertSame(
            $expectedOutput,
            (new PluginOutput($input))->render(),
            'PluginOutput::render does not return expected values'
        );
    }

    public function testRenderTextWithStatesAndCharacterLimit()
    {
        $input = <<<'INPUT'
[OK] Dummy state
    \_ [OK] Fake "state"
    \_ [WARNING] Fake state again
INPUT;

        $expectedOutput = <<<'EXPECTED_OUTPUT'
<span class="state-ball ball-size-m state-ok"></span> Dummy
EXPECTED_OUTPUT;

        $this->assertSame(
            $expectedOutput,
            (new PluginOutput($input))->setCharacterLimit(10)->render(),
            'PluginOutput::render does not return expected values'
        );
    }

    public function testRenderTextWithHtml()
    {
        $input = <<<'INPUT'
Hello <h3>World</h3>, this "is" 'a <strong>test</strong>.
INPUT;

        $expectedOutput = <<<'EXPECTED_OUTPUT'
Hello <h3>World</h3>, this "is" 'a <strong>test</strong>.
EXPECTED_OUTPUT;

        $this->assertSame(
            $expectedOutput,
            (new PluginOutput($input))->render(),
            'PluginOutput::render does not return expected values'
        );
    }

    public function testRenderTextWithHtmlAndStates()
    {
        $input = <<<'INPUT'
Hello <h3>World</h3>, this "is" a <strong>test</strong>.
[OK] Dummy state
    \_ [OK] Fake "state"
    \_ [WARNING] Fake state again
text <span> ends </span> here
INPUT;

        $expectedOutput = <<<'EXPECTED_OUTPUT'
Hello <h3>World</h3>, this "is" a <strong>test</strong>.
<span class="state-ball ball-size-m state-ok"></span> Dummy state
    \_ <span class="state-ball ball-size-m state-ok"></span> Fake "state"
    \_ <span class="state-ball ball-size-m state-warning"></span> Fake state again
text <span> ends </span> here
EXPECTED_OUTPUT;

        $this->assertSame(
            $expectedOutput,
            (new PluginOutput($input))->render(),
            'PluginOutput::render does not return expected values'
        );
    }

    public function testRenderTextWithHtmlIncludingStatesAndSpecialChars()
    {
        $input = <<<'INPUT'
Hello <h3>World</h3>, this "is" a <strong>test</strong>.
[OK] Dummy state
    special chars: !@#$%^&*()_+{}|:"<>?`-=[]\;',./
text <span> ends </span> here
INPUT;

        $expectedOutput = <<<'EXPECTED_OUTPUT'
Hello <h3>World</h3>, this "is" a <strong>test</strong>.
<span class="state-ball ball-size-m state-ok"></span> Dummy state
    special chars: !@#$%^&amp;*()_+{}|:"&lt;&gt;?`-=[]\;',&#x200B;./
text <span> ends </span> here
EXPECTED_OUTPUT;

        $this->assertSame(
            $expectedOutput,
            (new PluginOutput($input))->render(),
            'PluginOutput::render does not return expected values'
        );
    }
}
