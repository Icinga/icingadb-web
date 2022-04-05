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
use ipl\Html\HtmlElement;
use ipl\Orm\Exception\InvalidColumnException;
use ipl\Orm\Exception\InvalidRelationException;
use ipl\Orm\Model;
use ipl\Orm\Relation\BelongsToMany;
use ipl\Orm\Resolver;
use ipl\Orm\UnionModel;
use ipl\Sql\Cursor;
use ipl\Sql\Expression;
use ipl\Sql\Select;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Seq;
use ipl\Web\Control\SearchBar\SearchException;
use ipl\Web\Control\SearchBar\Suggestions;
use PDO;

class ObjectSuggestions extends Suggestions
{
    use Auth;
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
            'user'                  => t('User %s', '..<customvar-name>'),
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

    protected function createQuickSearchFilter($searchTerm)
    {
        $model = $this->getModel();

        $quickFilter = Filter::any();
        foreach ($model->getSearchColumns() as $column) {
            $where = Filter::like($model->getTableName() . '.' . $column, $searchTerm);
            $where->metaData()->set('columnLabel', $model->getMetaData()[$column]);
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
        list($targetPath, $columnName) = preg_split('/(?<=vars)\.|\.(?=[^.]+$)/', $columnPath);

        if (strpos($targetPath, '.') !== false) {
            try {
                $query->with($targetPath); // TODO: Remove this, once ipl/orm does it as early
            } catch (InvalidRelationException $e) {
                throw new SearchException(sprintf(t('"%s" is not a valid relation'), $e->getRelation()));
            }
        }

        if (substr($targetPath, -5) === '.vars') {
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
        $this->applyRestrictions($query);

        try {
            return (new Cursor($query->getDb(), $query->assembleSelect()->distinct()))
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
        foreach ($this->getDb()->select($this->queryCustomvarConfig($searchTerm)) as $customVar) {
            $search = $name = $customVar->flatname;
            if (preg_match('/\w+\[(\d+)]$/', $search, $matches)) {
                // array vars need to be specifically handled
                if ($matches[1] !== '0') {
                    continue;
                }

                $name = substr($search, 0, -3);
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
        if (preg_match('/_(?>id|bin|checksum)$/', $path)) {
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

                $this->applyRestrictions($query);

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
        $metaData = $resolver->getMetaData($model);
        foreach ($metaData as $columnName => $columnMeta) {
            yield $columnName => $columnMeta;
        }

        foreach ($resolver->getBehaviors($model) as $behavior) {
            if ($behavior instanceof ReRoute) {
                foreach ($behavior->getRoutes() as $name => $route) {
                    $relation = $resolver->resolveRelation(
                        $resolver->qualifyPath($route, $model->getTableName()),
                        $model
                    );
                    foreach ($relation->getTarget()->getMetaData() as $columnName => $columnMeta) {
                        yield $name . '.' . $columnName => $columnMeta;
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
            $foreignMetaDataSources[] = ['host.user', ' (' . t('Host') . ')'];
            $foreignMetaDataSources[] = ['host.usergroup', ' (' . t('Host') . ')'];
        }

        if (! $model instanceof Service) {
            $foreignMetaDataSources[] = ['service.user', ' (' . t('Service') . ')'];
            $foreignMetaDataSources[] = ['service.usergroup', ' (' . t('Service') . ')'];
        }

        foreach ($foreignMetaDataSources as list($path, $suffix)) {
            $foreignMetaData = $resolver->resolveRelation(
                $resolver->qualifyPath($path, $model->getTableName()),
                $model
            )
                ->getTarget()
                ->getMetaData();
            foreach ($foreignMetaData as $columnName => $columnMeta) {
                yield "$path.$columnName" => $columnMeta . $suffix;
            }
        }
    }
}
