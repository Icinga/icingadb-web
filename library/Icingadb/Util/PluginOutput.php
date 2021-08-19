<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Util;

use DOMDocument;
use DOMNode;
use DOMText;
use Icinga\Module\Icingadb\Hook\PluginOutputHook;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Web\Dom\DomNodeIterator;
use Icinga\Web\Helper\HtmlPurifier;
use InvalidArgumentException;
use ipl\Html\HtmlString;
use ipl\Orm\Model;
use LogicException;
use RecursiveIteratorIterator;

class PluginOutput extends HtmlString
{
    /** @var string[] Patterns to be replaced in plain text plugin output */
    const TEXT_PATTERNS = [
        '~\\\n\\\n~',
        '~\\\t~',
        '~\\\n~',
        '~(\[|\()OK(\]|\))~',
        '~(\[|\()WARNING(\]|\))~',
        '~(\[|\()CRITICAL(\]|\))~',
        '~(\[|\()UNKNOWN(\]|\))~',
        '~(\[|\()UP(\]|\))~',
        '~(\[|\()DOWN(\]|\))~',
        '~\@{6,}~'
    ];

    /** @var string[] Replacements for {@see PluginOutput::TEXT_PATTERNS} */
    const TEXT_REPLACEMENTS = [
        "\n",
        "\t",
        "\n",
        '<span class="state-ball ball-size-m state-ok"></span>',
        '<span class="state-ball ball-size-m state-warning"></span>',
        '<span class="state-ball ball-size-m state-critical"></span>',
        '<span class="state-ball ball-size-m state-unknown"></span>',
        '<span class="state-ball ball-size-m state-up"></span>',
        '<span class="state-ball ball-size-m state-down"></span>',
        '@@@@@@'
    ];

    /** @var string[] Patterns to be replaced in html plugin output */
    const HTML_PATTERNS = [
        '~\\\n\\\n~',
        '~\\\t~',
        '~\\\n~'
    ];

    /** @var string[] Replacements for {@see PluginOutput::HTML_PATTERNS} */
    const HTML_REPLACEMENTS = [
        "\n",
        "\t",
        "\n"
    ];

    /** @var string Already rendered output */
    protected $renderedOutput;

    /** @var bool Whether the output contains HTML */
    protected $isHtml;

    /** @var bool Whether output will be enriched */
    protected $enrichOutput = true;

    /** @var string The name of the command that produced the output */
    protected $commandName;

    /**
     * Get whether the output contains HTML
     *
     * Requires the output being already rendered.
     *
     * @return bool
     *
     * @throws LogicException In case the output hasn't been rendered yet
     */
    public function isHtml(): bool
    {
        if ($this->isHtml === null) {
            throw new LogicException('Output not rendered yet');
        }

        return $this->isHtml;
    }

    /**
     * Set whether the output should be enriched
     *
     * @param bool $state
     *
     * @return $this
     */
    public function setEnrichOutput(bool $state = true): self
    {
        $this->enrichOutput = $state;

        return $this;
    }

    /**
     * Set name of the command that produced the output
     *
     * @param string $name
     *
     * @return $this
     */
    public function setCommandName(string $name): self
    {
        $this->commandName = $name;

        return $this;
    }

    /**
     * Render plugin output of the given object
     *
     * @param Host|Service $object
     *
     * @return static
     *
     * @throws InvalidArgumentException If $object is neither a host nor a service
     */
    public static function fromObject(Model $object): self
    {
        if (! $object instanceof Host && ! $object instanceof Service) {
            throw new InvalidArgumentException(
                sprintf('Object is not a host or service, got %s instead', get_class($object))
            );
        }

        return (new static($object->state->output . "\n" . $object->state->long_output))
            ->setCommandName($object->checkcommand);
    }

    public function render()
    {
        if ($this->renderedOutput !== null) {
            return $this->renderedOutput;
        }

        $output = parent::render();
        if (empty($output)) {
            return '';
        }

        if ($this->commandName !== null) {
            $output = PluginOutputHook::processOutput($output, $this->commandName, $this->enrichOutput);
        }

        if (preg_match('~<\w+(?>\s\w+=[^>]*)?>~', $output)) {
            // HTML
            $output = preg_replace(
                self::HTML_PATTERNS,
                self::HTML_REPLACEMENTS,
                HtmlPurifier::process($output)
            );
            $this->isHtml = true;
        } else {
            // Plaintext
            $output = preg_replace(
                self::TEXT_PATTERNS,
                self::TEXT_REPLACEMENTS,
                htmlspecialchars($output, ENT_COMPAT | ENT_SUBSTITUTE | ENT_HTML5, null, false)
            );
            $this->isHtml = false;
        }

        $output = trim($output);

        // Add zero-width space after commas which are not followed by a whitespace character
        // in oder to help browsers to break words in plugin output
        $output = preg_replace('/,(?=[^\s])/', ',&#8203;', $output);

        if ($this->enrichOutput && $this->isHtml) {
            $output = $this->processHtml($output);
        }

        $this->renderedOutput = $output;

        return $output;
    }

    /**
     * Replace color state information, if any
     *
     * @param   string  $html
     *
     * @todo Do we really need to create a DOM here? Or is a preg_replace like we do it for text also feasible?
     * @return  string
     */
    protected function processHtml($html)
    {
        $pattern = '/[([](OK|WARNING|CRITICAL|UNKNOWN|UP|DOWN)[)\]]/';
        $doc = new DOMDocument();
        $doc->loadXML('<div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        $dom = new RecursiveIteratorIterator(new DomNodeIterator($doc), RecursiveIteratorIterator::SELF_FIRST);

        $nodesToRemove = [];
        foreach ($dom as $node) {
            /** @var DOMNode $node */
            if ($node->nodeType !== XML_TEXT_NODE) {
                continue;
            }

            $start = 0;
            while (preg_match($pattern, $node->nodeValue, $match, PREG_OFFSET_CAPTURE, $start)) {
                $offsetLeft = $match[0][1];
                $matchLength = strlen($match[0][0]);
                $leftLength = $offsetLeft - $start;

                // if there is text before the match
                if ($leftLength) {
                    // create node for leading text
                    $text = new DOMText(substr($node->nodeValue, $start, $leftLength));
                    $node->parentNode->insertBefore($text, $node);
                }

                // create the state ball for the match
                $span = $doc->createElement('span');
                $span->setAttribute(
                    'class',
                    'state-ball ball-size-m state-' . strtolower($match[1][0])
                );
                $node->parentNode->insertBefore($span, $node);

                // start for next match
                $start = $offsetLeft + $matchLength;
            }

            if ($start) {
                // is there text left?
                if (strlen($node->nodeValue) > $start) {
                    // create node for trailing text
                    $text = new DOMText(substr($node->nodeValue, $start));
                    $node->parentNode->insertBefore($text, $node);
                }

                // delete the old node later
                $nodesToRemove[] = $node;
            }
        }

        foreach ($nodesToRemove as $node) {
            /** @var DOMNode $node */
            $node->parentNode->removeChild($node);
        }

        return substr($doc->saveHTML(), 5, -7);
    }
}
