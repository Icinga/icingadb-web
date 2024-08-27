<?php

/** Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Hook;

use Icinga\Application\Hook;
use Icinga\Application\Logger;
use Icinga\Module\Icingadb\Hook\Common\HookUtils;
use ipl\Orm\Model;
use Throwable;

abstract class CustomVarEnricherHook
{
    use HookUtils;

    /**
     * Return the grouped custom vars
     *
     * @return array
     */
    abstract public function getGroups(): array;

    /**
     * Return enriched vars in the following format
     * [label => enriched custom var]
     *
     * @param array $vars
     *
     * @return array
     */
    abstract public function enrichCustomVars(array &$vars, Model $object): array;

    public static function prepareEnrichedCustomVars(array $vars, Model $object): array
    {
        $enrichedVars = [];
        $groups = [];

        foreach (Hook::all('Icingadb/CustomVarEnricher') as $hook) {
            /** @var self $hook */
            try {
                $enrichedVars[] = $hook->enrichCustomVars($vars, $object);
                $groups[] = $hook->getGroups();
            } catch (Throwable $e) {
                Logger::error('Failed to load hook %s:', get_class($hook), $e);
            }
        }

        $vars = array_merge($vars, ...$enrichedVars);
        $groups = array_merge([], ...$groups);

        return [$vars, $groups];
    }
}
