<?php

// SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Web\Control\SearchBar;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Data\QueryColumnsProvider;
use Icinga\Module\Icingadb\Util\ObjectSuggestionsCursor;
use ipl\Html\HtmlElement;
use ipl\Orm\Exception\InvalidColumnException;
use ipl\Orm\Exception\InvalidRelationException;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Stdlib\BaseFilter;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Seq;
use ipl\Web\Control\SearchBar\SearchException;
use ipl\Web\Control\SearchBar\Suggestions;
use PDO;

class ObjectSuggestions extends Suggestions
{
    use Auth;
    use BaseFilter;
    use Database;

    /** @var Model */
    protected $model;

    /** @var array */
    protected $customVarSources;

    /** @var ?array<string, string> */
    protected ?array $fixedColumns = null;

    public function __construct()
    {
        $this->customVarSources = QueryColumnsProvider::getDefaultCustomVarSources();
    }

    /**
     * Set the model to show suggestions for
     *
     * @param string|Model $model
     *
     * @return $this
     */
    public function setModel($model): self
    {
        if (is_string($model)) {
            $model = new $model();
        }

        $this->model = $model;

        return $this;
    }

    /**
     * Get the model to show suggestions for
     *
     * @return Model
     */
    public function getModel(): Model
    {
        if ($this->model === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->model;
    }

    protected function shouldShowRelationFor(string $column): bool
    {
        if (strpos($column, '.vars.') !== false) {
            return false;
        }

        $tableName = $this->getModel()->getTableName();
        $columnPath = explode('.', $column);

        switch (count($columnPath)) {
            case 3:
                if ($columnPath[1] !== 'state' || ! in_array($tableName, ['host', 'service'])) {
                    return true;
                }

                // For host/service state relation columns apply the same rules
            case 2:
                return $columnPath[0] !== $tableName;
            default:
                return true;
        }
    }

    private function applyBaseFilter(Query $query): void
    {
        $this->applyRestrictions($query);

        if ($this->hasBaseFilter()) {
            $query->filter($this->getBaseFilter());
        }
    }

    protected function createQuickSearchFilter($searchTerm)
    {
        $model = $this->getModel();
        $resolver = $model::on($this->getDb())->getResolver();

        $quickFilter = Filter::any();
        foreach ($model->getSearchColumns() as $column) {
            if (strpos($column, '.') === false) {
                $column = $resolver->qualifyColumn($column, $model->getTableName());
            }

            $where = Filter::like($column, $searchTerm);
            $where->metaData()->set('columnLabel', $resolver->getColumnDefinition($column)->getLabel());
            $quickFilter->add($where);
        }

        return $quickFilter;
    }

    protected function fetchValueSuggestions($column, $searchTerm, Filter\Chain $searchFilter)
    {
        $model = $this->getModel();
        $query = $model::on($this->getDb());
        $query->limit(static::DEFAULT_LIMIT);

        if (strpos($column, ' ') !== false) {
            // $column may be a label
            list($path, $_) = Seq::find(
                $this->fixedColumns
                    ?? QueryColumnsProvider::collectFilterColumns($query->getModel(), $query->getResolver()),
                $column,
                false
            );
            if ($path !== null) {
                $column = $path;
            }
        }

        $columnPath = $query->getResolver()->qualifyPath($column, $model->getTableName());
        list($targetPath, $columnName) = preg_split('/(?<=vars)\.|\.(?=[^.]+$)/', $columnPath, 2);

        $isCustomVar = false;
        if (substr($targetPath, -5) === '.vars') {
            $isCustomVar = true;
            $targetPath = substr($targetPath, 0, -4) . 'customvar_flat';
        }

        if (strpos($targetPath, '.') !== false) {
            try {
                $query->with($targetPath); // TODO: Remove this, once ipl/orm does it as early
            } catch (InvalidRelationException $e) {
                throw new SearchException(sprintf(t('"%s" is not a valid relation'), $e->getRelation()));
            }
        }

        if ($isCustomVar) {
            $columnPath = $targetPath . '.flatvalue';
            $query->filter(Filter::like($targetPath . '.flatname', $columnName));
        }

        $inputFilter = Filter::like($columnPath, $searchTerm);
        $query->columns($columnPath);
        $query->orderBy($columnPath);

        // This had so many iterations, if it still doesn't work, consider removing it entirely :(
        if ($searchFilter instanceof Filter\None) {
            $query->filter($inputFilter);
        } elseif ($searchFilter instanceof Filter\All) {
            $searchFilter->add($inputFilter);

            // There may be columns part of $searchFilter which target the base table. These must be
            // optimized, otherwise they influence what we'll suggest to the user. (i.e. less)
            // The $inputFilter on the other hand must not be optimized, which it wouldn't, but since
            // we force optimization on its parent chain, we have to negate that.
            $searchFilter->metaData()->set('forceOptimization', true);
            $inputFilter->metaData()->set('forceOptimization', false);
        } else {
            $searchFilter = $inputFilter;
        }

        $query->filter($searchFilter);
        $this->applyBaseFilter($query);

        try {
            return (new ObjectSuggestionsCursor($query->getDb(), $query->assembleSelect()->distinct()))
                ->setFetchMode(PDO::FETCH_COLUMN);
        } catch (InvalidColumnException $e) {
            throw new SearchException(sprintf(t('"%s" is not a valid column'), $e->getColumn()));
        }
    }

    protected function fetchColumnSuggestions($searchTerm)
    {
        $model = $this->getModel();
        $query = $model::on($this->getDb());

        $provider = (new QueryColumnsProvider($query, $searchTerm))
            ->setCustomVarSources($this->customVarSources);

        if ($this->fixedColumns !== null) {
            $provider->setFixedColumns($this->fixedColumns);
        }

        if ($this->hasBaseFilter()) {
            $provider->setBaseFilter($this->getBaseFilter());
        }

        $currentGroup = null;
        foreach ($provider as $item) {
            if (isset($item['group']) && $item['group'] !== $currentGroup) {
                $currentGroup = $item['group'];
                $this->addHtml(HtmlElement::create(
                    'li',
                    ['class' => static::SUGGESTION_TITLE_CLASS],
                    $currentGroup
                ));
            }

            yield $item['search'] => $item['label'];
        }
    }

    protected function filterColumnSuggestions($data, $searchTerm)
    {
        // Remove filtering here, as fetchColumnSuggestions already performs it
        yield from $data;
    }

    /**
     * Reduce {@see $customVarSources} to only given relations to fetch variables from
     *
     * @param string[] $relations
     *
     * @return $this
     */
    public function onlyWithCustomVarSources(array $relations): self
    {
        $this->customVarSources = array_intersect_key($this->customVarSources, array_flip($relations));

        return $this;
    }

    /**
     * Provide suggestions based on a fixed set of columns
     *
     * @param array<string, string> $columns
     *
     * @return $this
     */
    public function withFixedColumns(array $columns): static
    {
        $this->fixedColumns = $columns;

        return $this;
    }
}
