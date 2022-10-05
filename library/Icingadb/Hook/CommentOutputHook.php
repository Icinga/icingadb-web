<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Hook;

use Exception;
use Icinga\Application\Hook;
use Icinga\Application\Logger;
use Icinga\Module\Icingadb\Hook\Common\HookUtils;

abstract class CommentOutputHook
{
    use HookUtils;

    /**
     * Transform the given comment text
     *
     * Try to transform the comment output as efficient and fast as possible.
     * Especially list view performance may suffer otherwise.
     *
     * @param string $output A host's, service's, downtime's or an acknowledgement's comment
     *
     * @return string
     */
    abstract public function transformComment(string $output): string;


    /**
     * Let all hooks process the given comment text
     *
     * @param string $output A host's, service's, downtime's or an acknowledgement's comment
     *
     * @return string
     */
    final public static function processComment(string $output): string
    {
        foreach (Hook::all('Icingadb\\CommentOutput') as $hook) {
            /** @var self $hook */
            try {
                $output = $hook->transformComment($output);
            } catch (Exception $e) {
                Logger::error("Unable to process comment: %s\n%s", $e, $e->getTraceAsString());
            }
        }

        return $output;
    }
}
