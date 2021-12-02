<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Hook;

use Closure;
use Exception;
use Icinga\Application\Hook;
use Icinga\Application\Logger;
use Icinga\Module\Icingadb\Hook\Common\HookUtils;
use ipl\Orm\Model;

abstract class CustomVarRendererHook
{
    use HookUtils;

    /**
     * Prefetch the data the hook needs to render custom variables
     *
     * @param Model $object The object for which they'll be rendered
     *
     * @return bool Return true if the hook can render variables for the given object, false otherwise
     */
    abstract public function prefetchForObject(Model $object): bool;

    /**
     * Render the given variable name
     *
     * @param string $key
     *
     * @return ?mixed
     */
    abstract public function renderCustomVarKey(string $key);

    /**
     * Render the given variable value
     *
     * @param string $key
     * @param mixed $value
     *
     * @return ?mixed
     */
    abstract public function renderCustomVarValue(string $key, $value);

    /**
     * Return a group name for the given variable name
     *
     * @param string $key
     *
     * @return ?string
     */
    abstract public function identifyCustomVarGroup(string $key): ?string;

    /**
     * Prepare available hooks to render custom variables of the given object
     *
     * @param Model $object
     *
     * @return Closure A callback ($key, $value) which returns an array [$newKey, $newValue, $group]
     */
    final public static function prepareForObject(Model $object): Closure
    {
        $hooks = [];
        foreach (Hook::all('Icingadb/CustomVarRenderer') as $hook) {
            /** @var self $hook */
            try {
                if ($hook->prefetchForObject($object)) {
                    $hooks[] = $hook;
                }
            } catch (Exception $e) {
                Logger::error('Failed to load hook %s:', get_class($hook), $e);
            }
        }

        return function (string $key, $value) use ($hooks, $object) {
            $newKey = $key;
            $newValue = $value;
            $group = null;
            foreach ($hooks as $hook) {
                /** @var self $hook */

                try {
                    $renderedKey = $hook->renderCustomVarKey($key);
                    $renderedValue = $hook->renderCustomVarValue($key, $value);
                    $group = $hook->identifyCustomVarGroup($key);
                } catch (Exception $e) {
                    Logger::error('Failed to use hook %s:', get_class($hook), $e);
                    continue;
                }

                if ($renderedKey !== null || $renderedValue !== null) {
                    $newKey = $renderedKey ?? $key;
                    $newValue = $renderedValue ?? $value;
                    break;
                }
            }

            return [$newKey, $newValue, $group];
        };
    }
}
