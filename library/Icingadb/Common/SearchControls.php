<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use ipl\Html\Html;
use ipl\Orm\Exception\InvalidRelationException;
use ipl\Orm\Query;
use ipl\Orm\UnionQuery;
use ipl\Stdlib\Filter;
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

    public function fetchFilterColumns(Query $query): array
    {
        return iterator_to_array(ObjectSuggestions::collectFilterColumns($query->getModel(), $query->getResolver()));
    }

    private function callHandleRequest(): bool
    {
        return false;
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

        $columnValidator = function (SearchBar\ValidatedColumn $column) use ($query) {
            $columnPath = $column->getSearchValue();
            if (($pos = strpos($columnPath, '.vars.')) !== false) {
                $column->setMessage(null); // webCreateSearchBar will have set one

                try {
                    if ($query instanceof UnionQuery) {
                        // TODO: This can't be right. Finally solve this god-damn union-query-model structure!!!1
                        $queries = $query->getUnions();
                        $query = end($queries);
                    }

                    $relationPath = $query->getResolver()->qualifyPath(
                        substr($columnPath, 0, $pos + 5),
                        $query->getModel()->getTableName()
                    );
                    $query->getResolver()->resolveRelation($relationPath);
                } catch (InvalidRelationException $e) {
                    $column->setMessage(sprintf(
                        t('"%s" is not a valid relation'),
                        substr($e->getRelation(), 0, $pos)
                    ));
                }
            }
        };

        $searchBar->on(SearchBar::ON_ADD, $columnValidator)
            ->on(SearchBar::ON_INSERT, $columnValidator)
            ->on(SearchBar::ON_SAVE, $columnValidator)
            ->handleRequest(ServerRequest::fromGlobals());

        Html::tag('div', ['class' => 'filter'])->wrap($searchBar);

        return $searchBar;
    }

    /**
     * Create and return the SearchEditor
     *
     * @param Query $query The query being filtered
     * @param Url $redirectUrl Url to redirect to upon success
     * @param array $preserveParams Query params to preserve when redirecting
     *
     * @return SearchEditor
     */
    public function createSearchEditor(Query $query, ...$params): SearchEditor
    {
        $editor = $this->webCreateSearchEditor($query, ...$params);

        $editor->on(SearchEditor::ON_VALIDATE_COLUMN, function (Filter\Condition $condition) use ($query) {
            $column = $condition->getColumn();
            if (($pos = strpos($column, '.vars.')) !== false) {
                try {
                    $query->getResolver()->resolveRelation(substr($column, 0, $pos + 5));
                } catch (InvalidRelationException $e) {
                    throw new SearchBar\SearchException(sprintf(
                        t('"%s" is not a valid relation'),
                        substr($e->getRelation(), 0, $pos)
                    ));
                }
            }
        })->handleRequest(ServerRequest::fromGlobals());

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
