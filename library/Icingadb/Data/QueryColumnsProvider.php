<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Data;

use Generator;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use Icinga\Module\Icingadb\Model\CustomvarFlat;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Orm\Relation;
use ipl\Orm\Relation\BelongsToMany;
use ipl\Orm\Relation\HasOne;
use ipl\Orm\Resolver;
use ipl\Orm\UnionModel;
use ipl\Sql\Expression;
use ipl\Sql\Select;
use ipl\Stdlib\BaseFilter;
use ipl\Stdlib\Filter;
use ipl\Web\Control\SearchBar\Suggestions;
use ipl\Web\FormElement\SearchSuggestions;
use IteratorAggregate;

/**
 * Provide column and custom variable suggestions for a given query
 */
class QueryColumnsProvider implements IteratorAggregate
{
    use Auth;
    use BaseFilter;
    use Database;
    use Translation;

    /** @var Query The query to collect columns from */
    protected Query $query;

    /** @var string The search to suggest columns for */
    protected string $searchTerm;

    /** @var ?array<string, string> Fixed columns as column => label, if null columns are selected from $query */
    protected ?array $fixedColumns = null;

    /** @var array<string, string> Relations to suggest customvars from */
    protected array $customVarSources = [];

    /** @var array<string> Columns not to include in the result */
    protected array $excludedColumns = [];

    /** @var bool Whether to include relation path in column labels */
    protected bool $showRelationLabels = false;

    /**
     * Create a new QueryColumnsProvider
     *
     * @param Query $query
     * @param string $searchTerm
     */
    public function __construct(Query $query, string $searchTerm = '*')
    {
        $this->query = $query;
        $this->searchTerm = $searchTerm;
    }

    /**
     * Set a fixed set of columns to suggest columns from
     *
     * @param array<string, string> $columns Columns as keys and labels as values
     *
     * @return $this
     */
    public function setFixedColumns(array $columns): static
    {
        $this->fixedColumns = $columns;

        return $this;
    }

    /**
     * Set the custom variable sources to use
     *
     * @param array<string, string> $customVarSources
     *
     * @return $this
     */
    public function setCustomVarSources(array $customVarSources): static
    {
        $this->customVarSources = $customVarSources;

        return $this;
    }

    /**
     * Set the search term to suggest columns for
     *
     * @param string $searchTerm
     *
     * @return $this
     */
    public function setSearchTerm(string $searchTerm): static
    {
        $this->searchTerm = $searchTerm;

        return $this;
    }

    /**
     * Set columns to exclude from the suggestions
     *
     * @param array $columns
     *
     * @return $this
     */
    public function setExcludedColumns(array $columns): static
    {
        $this->excludedColumns = $columns;

        return $this;
    }

    public function getIterator(): Generator
    {
        $exactVarSearches = [];
        $parsedArrayVars = [];

        yield from $this->fetchExactCustomVars($exactVarSearches, $parsedArrayVars);
        yield from $this->fetchColumns();
        yield from $this->fetchRemainingCustomVars($exactVarSearches, $parsedArrayVars);
    }

    /**
     * Fetch custom variables that exactly match the search term
     *
     * @param string[] $exactVarSearches Collects matched flatnames to exclude from remaining results
     * @param array<string, true> $parsedArrayVars Already yielded array variable base names
     *
     * @return Generator
     */
    protected function fetchExactCustomVars(array &$exactVarSearches, array &$parsedArrayVars): Generator
    {
        $exactSearchTerm = trim($this->searchTerm, ' *');
        if ($exactSearchTerm === '') {
            return;
        }

        foreach (
            $this->getDb()->select($this->queryCustomvarConfig(
                Filter::any(
                    Filter::equal('flatname', $exactSearchTerm),
                    Filter::like('flatname', $exactSearchTerm . '[*]')
                )
            )) as $customVar
        ) {
            $search = $name = $customVar->flatname;
            $exactVarSearches[] = $search;
            if (preg_match('/\w+(?:\[(\d*)])+$/', $search, $matches)) {
                $name = substr($search, 0, -(strlen($matches[1]) + 2));
                if (isset($parsedArrayVars[$name])) {
                    continue;
                }

                $parsedArrayVars[$name] = true;
                $search = $name . '[*]';
            }

            foreach ($this->customVarSources as $relation => $label) {
                if (isset($customVar->$relation)) {
                    yield [
                        'search' => $relation . '.vars.' . $search,
                        'label'  => sprintf($label, $name),
                        'group'  => $this->translate('Best Suggestions')
                    ];
                }
            }
        }
    }

    /**
     * Fetch model column suggestions matching the search term
     *
     * @return Generator
     */
    protected function fetchColumns(): Generator
    {
        $columns = $this->fixedColumns ?? self::collectFilterColumns(
            $this->query->getModel(),
            $this->query->getResolver()
        );
        foreach ($columns as $columnName => $columnMeta) {
            if (
                ! in_array($columnName, $this->excludedColumns, true)
                && $this->matchSuggestion($columnName, $columnMeta, $this->searchTerm)
            ) {
                $result = [
                    'search' => $columnName,
                    'label'  => $columnMeta,
                    'group'  => $this->translate('Columns')
                ];
                if ($this->showRelationLabels && static::shouldShowRelationFor($columnName, $this->query->getModel())) {
                    $result['details'] = $this->getLabelWithRelation($columnName, $columnMeta);
                }

                yield $result;
            }
        }
    }

    /**
     * Fetch custom variable suggestions that were not already yielded by {@see self::fetchExactCustomVars}
     *
     * @param string[] $exactVarSearches Flatnames to exclude
     * @param array<string, true> $parsedArrayVars Already yielded array variables
     *
     * @return Generator
     */
    protected function fetchRemainingCustomVars(array $exactVarSearches, array &$parsedArrayVars): Generator
    {
        if (! empty($exactVarSearches)) {
            $varFilter = Filter::all(
                Filter::like('flatname', $this->searchTerm),
                Filter::unequal('flatname', $exactVarSearches)
            );
        } else {
            $varFilter = Filter::like('flatname', $this->searchTerm);
        }

        foreach (
            $this->getDb()->select($this->queryCustomvarConfig($varFilter)) as $customVar
        ) {
            $search = $name = $customVar->flatname;
            if (preg_match('/\w+(?:\[(\d*)])+$/', $search, $matches)) {
                $name = substr($search, 0, -(strlen($matches[1]) + 2));
                if (isset($parsedArrayVars[$name])) {
                    continue;
                }

                $parsedArrayVars[$name] = true;
                $search = $name . '[*]';
            }

            foreach ($this->customVarSources as $relation => $label) {
                if (isset($customVar->$relation)) {
                    yield [
                        'search' => $relation . '.vars.' . $search,
                        'label'  => sprintf($label, $name),
                        'group'  => $this->translate('Custom Variables')
                    ];
                }
            }
        }
    }

    /**
     * Collect all columns of this model and its relations that can be used for filtering
     *
     * @param Model $model
     * @param Resolver $resolver
     *
     * @return Generator
     */
    public static function collectFilterColumns(Model $model, Resolver $resolver): Generator
    {
        if ($model instanceof UnionModel) {
            $models = [];
            foreach ($model->getUnions() as $union) {
                /** @var Model $unionModel */
                $unionModel = new $union[0]();
                $models[$unionModel->getTableName()] = $unionModel;
                self::collectRelations($resolver, $unionModel, $models, []);
            }
        } else {
            $models = [$model->getTableName() => $model];
            self::collectRelations($resolver, $model, $models, []);
        }

        /** @var Model $targetModel */
        foreach ($models as $path => $targetModel) {
            foreach ($resolver->getColumnDefinitions($targetModel) as $columnName => $definition) {
                yield $path . '.' . $columnName => $definition->getLabel();
            }
        }

        foreach ($resolver->getBehaviors($model) as $behavior) {
            if ($behavior instanceof ReRoute) {
                foreach ($behavior->getRoutes() as $name => $route) {
                    $relation = $resolver->resolveRelation(
                        $resolver->qualifyPath($route, $model->getTableName()),
                        $model
                    );
                    foreach ($resolver->getColumnDefinitions($relation->getTarget()) as $columnName => $definition) {
                        yield $name . '.' . $columnName => $definition->getLabel();
                    }
                }
            }
        }

        if ($model instanceof UnionModel) {
            $queries = $model->getUnions();
            $baseModelClass = end($queries)[0];
            $model = new $baseModelClass();
        }

        $foreignMetaDataSources = [];
        if (! $model instanceof Host) {
            $foreignMetaDataSources[] = 'host.user';
            $foreignMetaDataSources[] = 'host.usergroup';
        }

        if (! $model instanceof Service) {
            $foreignMetaDataSources[] = 'service.user';
            $foreignMetaDataSources[] = 'service.usergroup';
        }

        foreach ($foreignMetaDataSources as $path) {
            $foreignColumnDefinitions = $resolver->getColumnDefinitions($resolver->resolveRelation(
                $resolver->qualifyPath($path, $model->getTableName()),
                $model
            )->getTarget());
            foreach ($foreignColumnDefinitions as $columnName => $columnDefinition) {
                yield "$path.$columnName" => $columnDefinition->getLabel();
            }
        }
    }

    /**
     * Collect all direct relations of the given model
     *
     * A direct relation is either a direct descendant of the model
     * or a descendant of such related in a to-one cardinality.
     *
     * @param Resolver $resolver
     * @param Model $subject
     * @param array $models
     * @param array $path
     */
    protected static function collectRelations(Resolver $resolver, Model $subject, array &$models, array $path)
    {
        foreach ($resolver->getRelations($subject) as $name => $relation) {
            /** @var Relation $relation */
            if (
                empty($path) || (
                    ($name === 'state' && $path[count($path) - 1] !== 'last_comment')
                    || $name === 'last_comment'
                    || $name === 'notificationcommand' && $path[0] === 'notification'
                )
            ) {
                $relationPath = [$name];
                if ($relation instanceof HasOne && empty($path)) {
                    array_unshift($relationPath, $subject->getTableName());
                }

                $relationPath = array_merge($path, $relationPath);
                $models[join('.', $relationPath)] = $relation->getTarget();
                self::collectRelations($resolver, $relation->getTarget(), $models, $relationPath);
            }
        }
    }

    /**
     * Create a query to fetch all available custom variables matching the given filter
     *
     * @param Filter\Rule $filter
     *
     * @return Select
     */
    public function queryCustomvarConfig(Filter\Rule $filter): Select
    {
        $customVars = CustomvarFlat::on($this->getDb());
        $tableName = $customVars->getModel()->getTableName();
        $resolver = $customVars->getResolver();

        $excludedByRelation = [];
        foreach ($this->excludedColumns as $column) {
            foreach ($this->customVarSources as $relation => $_) {
                $prefix = $relation . '.vars.';
                if (str_starts_with($column, $prefix)) {
                    $excludedByRelation[$relation][] = substr($column, strlen($prefix));
                }
            }
        }

        $scalarQueries = [];
        $aggregates = ['flatname'];
        foreach ($resolver->getRelations($customVars->getModel()) as $name => $relation) {
            if (isset($this->customVarSources[$name]) && $relation instanceof BelongsToMany) {
                $query = $customVars->createSubQuery(
                    $relation->getTarget(),
                    $resolver->qualifyPath($name, $tableName)
                );

                $this->applyBaseFilter($query);
                $select = $query->assembleSelect();

                if (isset($excludedByRelation[$name])) {
                    $flatname = $resolver->qualifyColumn('flatname', $resolver->getAlias($customVars->getModel()));
                    $select->where(
                        "$flatname NOT IN (?)",
                        ...$excludedByRelation[$name]
                    );
                }

                $aggregates[$name] = new Expression("MAX($name)");
                $scalarQueries[$name] = $select
                    ->resetColumns()->columns(new Expression('1'))
                    ->limit(1);
            }
        }

        $customVars->columns('flatname');
        $this->applyRestrictions($customVars);
        $customVars->filter($filter);

        // applyRestrictions() does not hide protected vars, but since querying them is not possible anymore,
        // we have to. Otherwise, the user can choose a protected var and get an error.
        $protectedVarFilter = Filter::any();
        foreach ($this->getAuth()->getRestrictions('icingadb/protect/variables') as $restriction) {
            $protectedVarFilter->add($this->parseDenylist($restriction, 'flatname'));
        }

        $customVars->filter($protectedVarFilter);

        $idColumn = $resolver->qualifyColumn('id', $resolver->getAlias($customVars->getModel()));
        $customVars = $customVars->assembleSelect();

        $customVars->columns($scalarQueries);
        $customVars->groupBy($idColumn);
        $customVars->limit(Suggestions::DEFAULT_LIMIT);

        // This outer query exists only because there's no way to combine aggregates and sub queries (yet)
        return (new Select())->columns($aggregates)->from(['results' => $customVars])->groupBy('flatname');
    }

    /**
     * Prepare to act as provider for the given SearchSuggestions
     *
     * @param SearchSuggestions $suggestions
     *
     * @return $this
     */
    public function forSuggestions(SearchSuggestions $suggestions): static
    {
        $this->setSearchTerm($suggestions->getSearchTerm());
        $this->setExcludedColumns($suggestions->getExcludeTerms());
        $suggestions->setGroupingCallback(fn($x) => $x['group']);
        $this->showRelationLabels = true;

        return $this;
    }

    /**
     * Check whether the given column path and label match the search term
     *
     * Exotic columns (id, bin, checksum) are only matched if the user typed the exact suffix.
     *
     * @param string $path
     * @param string $label
     * @param string $searchTerm
     *
     * @return bool
     */
    protected function matchSuggestion($path, $label, $searchTerm)
    {
        if (preg_match('/[_.](id|bin|checksum)$/', $path)) {
            // Only suggest exotic columns if the user knows about them
            $trimmedSearch = trim($searchTerm, ' *');
            return substr($path, -strlen($trimmedSearch)) === $trimmedSearch;
        }

        return fnmatch($searchTerm, $label, FNM_CASEFOLD) || fnmatch($searchTerm, $path, FNM_CASEFOLD);
    }

    /**
     * Apply restrictions and the base filter to the given query
     *
     * @param Query $query
     */
    private function applyBaseFilter(Query $query): void
    {
        $this->applyRestrictions($query);

        if ($this->hasBaseFilter()) {
            $query->filter($this->getBaseFilter());
        }
    }

    /**
     * Get the label for the column with its relation path
     *
     * @param string $column
     * @param string $label
     *
     * @return ValidHtml
     */
    protected function getLabelWithRelation(string $column, string $label): ValidHtml
    {
        $relationPath = substr($column, 0, strrpos($column, '.'));
        $span = new HtmlElement(
            'span',
            Attributes::create(['class' => 'relation-path']),
            Text::create($relationPath)
        );

        return (new HtmlDocument())
            ->addHtml(Text::create($label))
            ->addHtml($span);
    }

    /**
     * Return whether the relation should be shown for the given column of the given model
     *
     * @param string $column
     * @param Model $model
     *
     * @return bool
     */
    public static function shouldShowRelationFor(string $column, Model $model): bool
    {
        if (str_contains($column, '.vars.')) {
            return false;
        }

        $tableName = $model->getTableName();
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
}
