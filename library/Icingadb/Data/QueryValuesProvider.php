<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Data;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Util\ObjectSuggestionsCursor;
use ipl\I18n\Translation;
use ipl\Orm\Exception\InvalidColumnException;
use ipl\Orm\Exception\InvalidRelationException;
use ipl\Orm\Query;
use ipl\Stdlib\BaseFilter;
use ipl\Stdlib\Filter;
use ipl\Web\Control\SearchBar\SearchException;
use IteratorAggregate;
use PDO;
use Traversable;

/**
 * Provide value suggestions for a given column and query
 */
class QueryValuesProvider implements IteratorAggregate
{
    use Auth;
    use BaseFilter;
    use Translation;

    /** @var Query The query to collect values from */
    protected Query $query;

    /** @var string The column to suggest values for */
    protected string $column;

    /** @var string The search term to suggest values for */
    protected string $searchTerm;

    /** @var Filter\Chain|Filter\Rule The search filter to apply */
    protected Filter\Chain|Filter\Rule $searchFilter;

    /**
     * Create a new QueryValuesProvider
     *
     * @param Query $query The query to collect values from
     * @param string $column The column to suggest values for
     * @param string $searchTerm The search term to suggest values for
     * @param Filter\Chain $searchFilter The search filter to apply
     */
    public function __construct(Query $query, string $column, string $searchTerm, Filter\Chain $searchFilter)
    {
        $this->query = $query;
        $this->column = $column;
        $this->searchTerm = $searchTerm;
        $this->searchFilter = $searchFilter;
    }

    public function getIterator(): Traversable
    {
        $columnPath = $this->query->getResolver()->qualifyPath($this->column, $this->query->getModel()->getTableName());
        [$targetPath, $columnName] = preg_split('/(?<=vars)\.|\.(?=[^.]+$)/', $columnPath, 2);

        $isCustomVar = false;
        if (str_ends_with($targetPath, '.vars')) {
            $isCustomVar = true;
            $targetPath = substr($targetPath, 0, -4) . 'customvar_flat';
        }

        if (str_contains($targetPath, '.')) {
            try {
                $this->query->with($targetPath); // TODO: Remove this, once ipl/orm does it as early
            } catch (InvalidRelationException $e) {
                throw new SearchException(sprintf($this->translate('"%s" is not a valid relation'), $e->getRelation()));
            }
        }

        if ($isCustomVar) {
            $columnPath = $targetPath . '.flatvalue';
            $this->query->filter(Filter::like($targetPath . '.flatname', $columnName));
        }

        $inputFilter = Filter::like($columnPath, $this->searchTerm);
        $this->query->columns($columnPath);
        $this->query->orderBy($columnPath);

        if ($this->searchFilter instanceof Filter\None) {
            $this->query->filter($inputFilter);
        } elseif ($this->searchFilter instanceof Filter\All) {
            $this->searchFilter->add($inputFilter);

            // There may be columns part of $searchFilter which target the base table. These must be
            // optimized, otherwise they influence what we'll suggest to the user. (i.e. less)
            // The $inputFilter on the other hand must not be optimized, which it wouldn't, but since
            // we force optimization on its parent chain, we have to negate that.
            $this->searchFilter->metaData()->set('forceOptimization', true);
            $inputFilter->metaData()->set('forceOptimization', false);
        } else {
            $this->searchFilter = $inputFilter;
        }

        $this->query->filter($this->searchFilter);

        $this->applyRestrictions($this->query);
        if ($this->hasBaseFilter()) {
            $this->query->filter($this->getBaseFilter());
        }

        try {
            return (new ObjectSuggestionsCursor($this->query->getDb(), $this->query->assembleSelect()->distinct()))
                ->setFetchMode(PDO::FETCH_COLUMN);
        } catch (InvalidColumnException $e) {
            throw new SearchException(sprintf($this->translate('"%s" is not a valid column'), $e->getColumn()));
        }
    }
}