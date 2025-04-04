<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Control\SearchBar;

use Generator;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use Icinga\Module\Icingadb\Model\CustomvarFlat;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Util\ObjectSuggestionsCursor;
use ipl\Html\HtmlElement;
use ipl\Orm\Exception\InvalidColumnException;
use ipl\Orm\Exception\InvalidRelationException;
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

    public function __construct()
    {
        $this->customVarSources = [
            'checkcommand'          => t('Checkcommand %s', '..<customvar-name>'),
            'eventcommand'          => t('Eventcommand %s', '..<customvar-name>'),
            'host'                  => t('Host %s', '..<customvar-name>'),
            'hostgroup'             => t('Hostgroup %s', '..<customvar-name>'),
            'notification'          => t('Notification %s', '..<customvar-name>'),
            'notificationcommand'   => t('Notificationcommand %s', '..<customvar-name>'),
            'service'               => t('Service %s', '..<customvar-name>'),
            'servicegroup'          => t('Servicegroup %s', '..<customvar-name>'),
            'timeperiod'            => t('Timeperiod %s', '..<customvar-name>'),
            'user'                  => t('Contact %s', '..<customvar-name>'),
            'usergroup'             => t('Usergroup %s', '..<customvar-name>')
        ];
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
                self::collectFilterColumns($query->getModel(), $query->getResolver()),
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

        // Ordinary columns first
        foreach (self::collectFilterColumns($model, $query->getResolver()) as $columnName => $columnMeta) {
            yield $columnName => $columnMeta;
        }

        // Custom variables only after the columns are exhausted and there's actually a chance the user sees them
        $titleAdded = false;
        $parsedArrayVars = [];
        foreach ($this->getDb()->select($this->queryCustomvarConfig($searchTerm)) as $customVar) {
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
                    if (! $titleAdded) {
                        $titleAdded = true;
                        $this->addHtml(HtmlElement::create(
                            'li',
                            ['class' => static::SUGGESTION_TITLE_CLASS],
                            t('Custom Variables')
                        ));
                    }

                    yield $relation . '.vars.' . $search => sprintf($label, $name);
                }
            }
        }
    }

    protected function matchSuggestion($path, $label, $searchTerm)
    {
        if (preg_match('/[_.](id|bin|checksum)$/', $path)) {
            // Only suggest exotic columns if the user knows about them
            $trimmedSearch = trim($searchTerm, ' *');
            return substr($path, -strlen($trimmedSearch)) === $trimmedSearch;
        }

        return parent::matchSuggestion($path, $label, $searchTerm);
    }

    /**
     * Create a query to fetch all available custom variables matching the given term
     *
     * @param string $searchTerm
     *
     * @return Select
     */
    protected function queryCustomvarConfig(string $searchTerm): Select
    {
        $customVars = CustomvarFlat::on($this->getDb());
        $tableName = $customVars->getModel()->getTableName();
        $resolver = $customVars->getResolver();

        $scalarQueries = [];
        $aggregates = ['flatname'];
        foreach ($resolver->getRelations($customVars->getModel()) as $name => $relation) {
            if (isset($this->customVarSources[$name]) && $relation instanceof BelongsToMany) {
                $query = $customVars->createSubQuery(
                    $relation->getTarget(),
                    $resolver->qualifyPath($name, $tableName)
                );

                $this->applyBaseFilter($query);

                $aggregates[$name] = new Expression("MAX($name)");
                $scalarQueries[$name] = $query->assembleSelect()
                    ->resetColumns()->columns(new Expression('1'))
                    ->limit(1);
            }
        }

        $customVars->columns('flatname');
        $this->applyRestrictions($customVars);
        $customVars->filter(Filter::like('flatname', $searchTerm));
        $idColumn = $resolver->qualifyColumn('id', $resolver->getAlias($customVars->getModel()));
        $customVars = $customVars->assembleSelect();

        $customVars->columns($scalarQueries);
        $customVars->groupBy($idColumn);
        $customVars->limit(static::DEFAULT_LIMIT);

        // This outer query exists only because there's no way to combine aggregates and sub queries (yet)
        return (new Select())->columns($aggregates)->from(['results' => $customVars])->groupBy('flatname');
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
}
