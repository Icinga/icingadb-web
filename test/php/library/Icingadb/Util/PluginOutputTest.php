<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Tests\Icinga\Module\Icingadb\Util;

use Icinga\Module\Icingadb\Util\PluginOutput;
use PHPUnit\Framework\TestCase;

class PluginOutputTest extends TestCase
{
    public function checkOutput(string $expected, string $input, int $characterLimit = 0): void
    {
        $p = new PluginOutput($input);

        if ($characterLimit) {
            $p->setCharacterLimit($characterLimit);
        }

        $this->assertSame($expected, $p->render(), 'PluginOutput::render does not return expected values');
    }


    public function testRenderPlainText(): void
    {
        $input = 'This is a plain text';

        $this->checkOutput($input, $input);
    }

    public function testRenderTextWithStates(): void
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

        $this->checkOutput($expectedOutput, $input);
    }

    public function testRenderTextWithStatesAndCharacterLimit(): void
    {
        $input = <<<'INPUT'
[OK] Dummy state
    \_ [OK] Fake "state"
    \_ [WARNING] Fake state again
INPUT;

        $expectedOutput = <<<'EXPECTED_OUTPUT'
<span class="state-ball ball-size-m state-ok"></span> Dummy
EXPECTED_OUTPUT;

        $this->checkOutput($expectedOutput, $input, 10);
    }

    public function testRenderTextWithHtml(): void
    {
        $input = <<<'INPUT'
Hello <h3>World</h3>, this "is" 'a <strong>test</strong>.
INPUT;

        $expectedOutput = <<<'EXPECTED_OUTPUT'
Hello <h3>World</h3>, this "is" 'a <strong>test</strong>.
EXPECTED_OUTPUT;

        $this->checkOutput($expectedOutput, $input);
    }

    public function testRenderTextWithHtmlAndStates(): void
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

        $this->checkOutput($expectedOutput, $input);
    }

    public function testRenderTextWithHtmlIncludingStatesAndSpecialChars(): void
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
    special chars: !@#$%^&amp;*()_+{}|:"&lt;&gt;?`-=[]\;',&#8203;./
text <span> ends </span> here
EXPECTED_OUTPUT;

        $this->checkOutput($expectedOutput, $input);
    }

    public function testOutputWithNewlines(): void
    {
        $input = 'foo\nbar\n\nraboof';
        $expectedOutput = "foo\nbar\n\nraboof";

        $this->checkOutput($expectedOutput, $input);
    }

    public function testOutputWithHtmlEntities(): void
    {
        $input = 'foo&nbsp;&amp;&nbsp;bar';
        $expectedOutput = $input;

        $this->checkOutput($expectedOutput, $input);
    }

    public function testSimpleHtmlOutput(): void
    {
        $input = <<<'INPUT'
OK - Teststatus <a href="http://localhost/test.php" target="_blank">Info</a>
INPUT;

        $expectedOutput = <<<'EXPECTED_OUTPUT'
OK - Teststatus <a href="http://localhost/test.php" target="_blank" rel="noreferrer noopener">Info</a>
EXPECTED_OUTPUT;

        $this->checkOutput($expectedOutput, $input);
    }

    public function testTextStatusTags(): void
    {
        foreach (['OK', 'WARNING', 'CRITICAL', 'UNKNOWN', 'UP', 'DOWN'] as $s) {
            $l = strtolower($s);

            $input = sprintf('[%s] Test', $s);
            $expectedOutput = sprintf('<span class="state-ball ball-size-m state-%s"></span> Test', $l);

            $this->checkOutput($expectedOutput, $input);

            $input = sprintf('(%s) Test', $s);

            $this->checkOutput($expectedOutput, $input);
        }
    }

    public function testHtmlStatusTags(): void
    {
        $dummyHtml = '<a href="#"></a>';

        foreach (['OK', 'WARNING', 'CRITICAL', 'UNKNOWN', 'UP', 'DOWN'] as $s) {
            $l = strtolower($s);

            $input = sprintf('%s [%s] Test', $dummyHtml, $s);
            $expectedOutput = sprintf('%s <span class="state-ball ball-size-m state-%s"></span> Test', $dummyHtml, $l);

            $this->checkOutput($expectedOutput, $input);

            $input = sprintf('%s (%s) Test', $dummyHtml, $s);

            $this->checkOutput($expectedOutput, $input);
        }
    }

    public function testNewlineProcessingInHtmlOutput(): void
    {
        $input = 'This is plugin output\n\n<ul>\n    <li>with a HTML list</li>\n</ul>\n\n'
            . 'and more text that\nis split onto multiple\n\nlines';

        $expectedOutput = <<<'EXPECTED_OUTPUT'
This is plugin output

<ul>
    <li>with a HTML list</li>
</ul>

and more text that
is split onto multiple

lines
EXPECTED_OUTPUT;

        $this->checkOutput($expectedOutput, $input);
    }
}
