<?php

// SPDX-FileCopyrightText: 2022 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Common;

use Icinga\Module\Icingadb\Hook\SearchColumnsProviderHook;
use ipl\Orm\Query;
use ipl\Web\Control\SearchBar;
use ipl\Web\Control\SearchEditor;
use ipl\Web\Url;
use ipl\Web\Widget\ContinueWith;

trait SearchControls
{
    use \ipl\Web\Compat\SearchControls {
        \ipl\Web\Compat\SearchControls::createSearchBar as private webCreateSearchBar;
        \ipl\Web\Compat\SearchControls::createSearchEditor as private webCreateSearchEditor;
    }

    /**
     * Create and return the SearchBar
     *
     * @param Query $query The query being filtered
     * @param Url $redirectUrl Url to redirect to upon success
     * @param array $preserveParams Query params to preserve when redirecting
     *
     * @return SearchBar
     */
    public function createSearchBar(Query $query, ...$params): SearchBar
    {
        $searchBar = $this->webCreateSearchBar($query, ...$params);

        $model = $query->getModel();
        $defaultColumns = $model->getSearchColumns();
        $columns = SearchColumnsProviderHook::getCustomVarColumns($model, $defaultColumns);
        $searchBar->setSearchColumns($columns);

        $searchBar->handleRequest($this->getServerRequest());

        return $searchBar;
    }

    private function callHandleRequest()
    {
        return false;
    }

    /**
     * Necessary because {@see self::callHandleRequest()} prevents the {@see webCreateSearchEditor()} from calling
     * $editor->handleRequest()
     *
     * @inheritdoc
     */
    public function createSearchEditor(Query $query, ...$params): SearchEditor
    {
        $editor = $this->webCreateSearchEditor($query, ...$params);
        $editor->handleRequest($this->getServerRequest());

        return $editor;
    }

    /**
     * Create and return a ContinueWith
     *
     * This will automatically be appended to the SearchBar's wrapper. It's not necessary
     * to add it separately as control or content!
     *
     * @param Url $detailsUrl
     * @param SearchBar $searchBar
     * @param bool $hasResults Whether the current query has results
     *
     * @return ContinueWith
     */
    public function createContinueWith(Url $detailsUrl, SearchBar $searchBar, bool $hasResults = true): ContinueWith
    {
        if ($hasResults) {
            $continueWith = ContinueWith::create(
                $detailsUrl,
                [$searchBar, 'getFilter'],
                t('Show bulk processing actions for all filtered results'),
                t('A filter is required to show bulk processing actions'),
            );
            $continueWith->setBaseTarget('_next');
        } else {
            $continueWith = ContinueWith::createDisabled(t('No items found'));
        }

        $continueWith->getAttributes()
            ->set('id', $this->getRequest()->protectId('continue-with'));

        $searchBar->getWrapper()->add($continueWith);

        return $continueWith;
    }
}
