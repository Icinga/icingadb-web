<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Hook;

use Exception;
use Icinga\Application\Hook;
use Icinga\Application\Logger;
use Icinga\Module\Icingadb\Hook\Common\HookUtils;

abstract class PluginOutputHook
{
    use HookUtils;

    /**
     * Return whether the given command is supported or not
     *
     * @param string $commandName
     *
     * @return bool
     */
    abstract public function isSupportedCommand(string $commandName): bool;

    /**
     * Process the given plugin output based on the specified check command
     *
     * Try to process the output as efficient and fast as possible.
     * Especially list view performance may suffer otherwise.
     *
     * @param string $output A host's or service's output
     * @param string $commandName The name of the checkcommand that produced the output
     * @param bool $enrichOutput Whether macros or other markup should be processed
     *
     * @return string
     */
    abstract public function render(string $output, string $commandName, bool $enrichOutput): string;

    /**
     * Let all hooks process the given plugin output based on the specified check command
     *
     * @param string $output
     * @param string $commandName
     * @param bool $enrichOutput
     *
     * @return string
     */
    final public static function processOutput(string $output, string $commandName, bool $enrichOutput): string
    {
        foreach (Hook::all('Icingadb\\PluginOutput') as $hook) {
            /** @var self $hook */
            try {
                if ($hook->isSupportedCommand($commandName)) {
                    $output = $hook->render($output, $commandName, $enrichOutput);
                }
            } catch (Exception $e) {
                Logger::error("Unable to process plugin output: %s\n%s", $e, $e->getTraceAsString());
            }
        }

        return $output;
    }
}
