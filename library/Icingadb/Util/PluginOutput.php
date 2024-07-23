<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Util;

use Icinga\Module\Icingadb\Hook\PluginOutputHook;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Web\Helper\HtmlPurifier;
use InvalidArgumentException;
use ipl\Html\HtmlString;
use ipl\Orm\Model;
use LogicException;

class PluginOutput extends HtmlString
{
    /** @var string[] Patterns to be replaced in plain text plugin output */
    const TEXT_PATTERNS = [
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
        '~\\\t~',
        '~\\\n~'
    ];

    /** @var string[] Replacements for {@see PluginOutput::HTML_PATTERNS} */
    const HTML_REPLACEMENTS = [
        "\t",
        "\n"
    ];

    /** @var string Already rendered output */
    protected $renderedOutput;

    /** @var bool Whether the output contains HTML */
    protected $isHtml;

    /** @var int The maximum amount of characters to process */
    protected $characterLimit = 1000;

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
            if (empty($this->getContent())) {
                // "Nothing" can't be HTML
                return false;
            }

            throw new LogicException('Output not rendered yet');
        }

        return $this->isHtml;
    }

    /**
     * Set the maximum amount of characters to process
     *
     * @param int $limit
     *
     * @return $this
     */
    public function setCharacterLimit(int $limit): self
    {
        $this->characterLimit = $limit;

        return $this;
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
            ->setCommandName($object->checkcommand_name);
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

        $output = substr($output, 0, $this->characterLimit);

        $this->isHtml = (bool) preg_match('~<\w+(?>\s\w+=[^>]*)?>~', $output);

        if ($this->isHtml) {
            if ($this->enrichOutput) {
                $output = preg_replace(self::TEXT_PATTERNS, self::TEXT_REPLACEMENTS, $output);
            } else {
                $output = preg_replace(self::HTML_PATTERNS, self::HTML_REPLACEMENTS, $output);
            }

            $output = HtmlPurifier::process($output);
        } else {
            $output = preg_replace(
                self::TEXT_PATTERNS,
                self::TEXT_REPLACEMENTS,
                htmlspecialchars($output, ENT_COMPAT | ENT_SUBSTITUTE | ENT_HTML5, null, false)
            );
        }

        $output = trim($output);

        // Add zero-width space after commas which are not followed by a whitespace character
        // in oder to help browsers to break words in plugin output
        $output = preg_replace('/,(?=[^\s])/', ',&#8203;', $output);

        $this->renderedOutput = $output;

        return $output;
    }
}
