<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\ProvidedHook\Dashboards;

use Icinga\Module\Icingadb\Common\Backend;
use Icinga\Module\Icingadb\Hook\SearchColumnsProviderHook;
use ipl\Orm\Exception\InvalidRelationException;
use ipl\Orm\Model;
use ipl\Stdlib\Seq;

/**
 * What a dashlet's filter asks of this module, answered from the model an object type is listed from
 *
 * Shared by the dashlet types whose filter is the search bar of one of this module's lists. The using class says
 * which model that is, per object type, and everything below follows from it.
 */
trait FilterColumns
{
    /**
     * Get the model the filter of an object type is validated and completed against, or null for none
     */
    abstract private function filterModel(?string $objectType): ?Model;

    /**
     * The columns a bare word is searched in, as the list's own search bar searches them
     *
     * `Model::getSearchColumns()` plus the custom variables a provider contributes
     * (`library/Icingadb/Common/SearchControls.php:34-38`).
     *
     * @return string[]
     */
    public function getFilterSearchColumns(?string $objectType): array
    {
        $model = $this->filterModel($objectType);

        return $model === null
            ? []
            : SearchColumnsProviderHook::getCustomVarColumns($model, $model->getSearchColumns());
    }

    /**
     * Resolve a column typed into the filter the way the list's own search bar does
     *
     * Copied from the validator `ipl\Web\Compat\SearchControls::createSearchBar()` registers
     * (`refs/ipl-web src/Compat/SearchControls.php:99-134`), minus the controller it reads its query from: a
     * path without a table is qualified, a path the model has no relation for is looked up among the column
     * definitions by name or label, and what is neither is refused.
     */
    public function resolveFilterColumn(?string $objectType, string $column): ?array
    {
        $model = $this->filterModel($objectType);

        if ($model === null) {
            return ['column' => $column, 'label' => $column];
        }

        $query = $model::on(Backend::getDb());
        $resolver = $query->getResolver();

        $qualified = strpos($column, '.') === false
            ? $resolver->qualifyPath($column, $query->getModel()->getTableAlias())
            : $column;

        try {
            $definition = $resolver->getColumnDefinition($column);
        } catch (InvalidRelationException $_) {
            $columns = [];

            foreach ($resolver->getColumnDefinitions($query->getModel()) as $name => $definition) {
                $columns[$name] = $definition->getLabel();
            }

            [$path, $label] = Seq::find($columns, $column, false);

            return $path === null ? null : ['column' => $path, 'label' => $label];
        }

        // A path that is a valid relation but names no column
        return $definition->getName() ? ['column' => $qualified, 'label' => $definition->getLabel()] : null;
    }
}
