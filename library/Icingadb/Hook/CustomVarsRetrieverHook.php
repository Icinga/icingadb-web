<?php

namespace Icinga\Module\Icingadb\Hook;

use Icinga\Application\Hook;
use Icinga\Application\Logger;
use ipl\Orm\Model;
use Throwable;

abstract class CustomVarsRetrieverHook
{
    abstract public function retrieveCustomVars(Model $model): array;

    final public static function getCustomVarColumns(Model $model): array
    {
        $columns = [];

        $hooks = Hook::all('Icingadb/CustomVarsRetriever');
        foreach ($hooks as $hook) {
            try {
                $customVars = $hook->retrieveCustomVars($model);
                if (!empty($customVars)) {
                    $columns = [...$columns, ...$customVars];
                }
            } catch (Throwable $e) {
                Logger::error(
                    'Error retrieving Custom Vars for %s: %s',
                    $object,
                    $e->getMessage()
                );
            }
        }

        return $columns;
    }
}
