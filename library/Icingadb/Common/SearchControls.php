<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use ipl\Html\Html;
use ipl\Orm\Query;
use ipl\Web\Control\SearchBar;
use ipl\Web\Url;
use ipl\Web\Widget\ContinueWith;

trait SearchControls
{
    use \ipl\Web\Compat\SearchControls {
        \ipl\Web\Compat\SearchControls::createSearchBar as private webCreateSearchBar;
    }

    public function fetchFilterColumns(Query $query): array
    {
        return iterator_to_array(ObjectSuggestions::collectFilterColumns($query->getModel(), $query->getResolver()));
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

        if (($wrapper = $searchBar->getWrapper()) && ! $wrapper->getWrapper()) {
            // TODO: Remove this once ipl-web v0.7.0 is required
            $searchBar->addWrapper(Html::tag('div', ['class' => 'search-controls']));
        }

        return $searchBar;
    }

    /**
     * Create and return a ContinueWith
     *
     * This will automatically be appended to the SearchBar's wrapper. It's not necessary
     * to add it separately as control or content!
     *
     * @param Url $detailsUrl
     * @param SearchBar $searchBar
     *
     * @return ContinueWith
     */
    public function createContinueWith(Url $detailsUrl, SearchBar $searchBar): ContinueWith
    {
        $continueWith = new ContinueWith($detailsUrl, [$searchBar, 'getFilter']);
        $continueWith->setTitle(t('Show bulk processing actions for all filtered results'));
        $continueWith->setBaseTarget('_next');
        $continueWith->getAttributes()
            ->set('id', $this->getRequest()->protectId('continue-with'));

        $searchBar->getWrapper()->add($continueWith);

        return $continueWith;
    }
}
