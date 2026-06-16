<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Hook;

use Icinga\Application\Hook;
use Icinga\Application\Logger;
use ipl\Orm\Model;
use Throwable;

/**
 * Hook for providing additional search columns such as custom variables.
 *
 * This is a first attempt to generalize support for modules to provide additional search columns.
 * The end user won't be able to customize the set provided and every global or route search will
 * always use them. Any hook implementation must ensure that only columns the user has access to
 * and that are valid in the current route are returned. Returning invalid columns may cause errors
 * for users. See https://github.com/Icinga/icingadb-web/security/advisories/GHSA-w57j-28jc-8429
 * for an example of what can go wrong.
 *
 * @deprecated This will not be used by any future version without any further notice.
 */
abstract class SearchColumnsProviderHook
{
    abstract public function retrieveCustomVars(Model $model): array;

    final public static function getCustomVarColumns(Model $model, array $defaultColumns = []): array
    {
        $columns = $defaultColumns;

        $hooks = Hook::all('icingadb/SearchColumnsProvider');
        foreach ($hooks as $hook) {
            try {
                $customVars = $hook->retrieveCustomVars($model);
                if (! empty($customVars)) {
                    $columns = [...$columns, ...$customVars];
                }
            } catch (Throwable $e) {
                Logger::error(
                    'Error retrieving Custom Vars for %s with table name "%s": %s',
                    get_class($model),
                    $model->getTableName(),
                    $e->getMessage()
                );
            }
        }

        return array_unique($columns);
    }
}
