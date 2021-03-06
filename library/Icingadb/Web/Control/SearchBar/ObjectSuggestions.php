<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Control\SearchBar;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\Behavior\ReRoute;
use Icinga\Module\Icingadb\Model\CustomvarFlat;
use InvalidArgumentException;
use ipl\Html\HtmlElement;
use ipl\Orm\Compat\FilterProcessor;
use ipl\Orm\Model;
use ipl\Orm\Relation\BelongsToMany;
use ipl\Orm\Resolver;
use ipl\Sql\Cursor;
use ipl\Sql\Expression;
use ipl\Sql\Select;
use ipl\Stdlib\Filter;
use ipl\Web\Control\SearchBar\SearchException;
use ipl\Web\Control\SearchBar\Suggestions;
use PDO;
use RuntimeException;

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
    public function setModel($model)
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
    public function getModel()
    {
        return $this->model;
    }

    protected function createQuickSearchFilter($searchTerm)
    {
        $model = $this->getModel();

        $quickFilter = Filter::any();
        foreach ($model->getSearchColumns() as $column) {
            $where = Filter::equal($model->getTableName() . '.' . $column, $searchTerm);
            $where->columnLabel = $model->getMetaData()[$column];
            $quickFilter->add($where);
        }

        return $quickFilter;
    }

    protected function fetchValueSuggestions($column, $searchTerm, Filter\Chain $searchFilter)
    {
        $model = $this->getModel();
        $query = $model::on($this->getDb());

        // TODO: Remove this once https://github.com/Icinga/ipl-orm/issues/9 is done
        foreach ($query->getResolver()->getBehaviors($model) as $behavior) {
            if ($behavior instanceof ReRoute) {
                $expr = Filter::equal('', '');
                $expr->relationCol = $column;
                $expr = $behavior->rewriteCondition($expr, '');
                if ($expr !== null) {
                    $column = $expr->getColumn();
                    break;
                }
            }
        }

        $columnPath = $query->getResolver()->qualifyPath($column, $model->getTableName());
        list($targetPath, $columnName) = preg_split('/(?<=vars)\.|\.(?=[^.]+$)/', $columnPath);

        if (strpos($targetPath, '.') !== false) {
            try {
                $query->with($targetPath); // TODO: Remove this, once ipl/orm does it as early
            } catch (InvalidArgumentException $_) {
                throw new SearchException(sprintf(t('"%s" is not a valid relation'), $targetPath));
            }
        }

        if (substr($targetPath, -5) === '.vars') {
            $columnPath = $targetPath . '.flatvalue';
            $flatnameFilter = Filter::equal($targetPath . '.flatname', $columnName);

            // So, this requires some explanation why it's necessary. Take a look into orm's FilterProcessor,
            // if you want to know what it does. It's necessary because said FilterProcessor optimizes every
            // single filter rule no matter if its relation is being selected or not. Most of the time this is
            // fine. But once there's a filter rule that targets the same relation that is also being selected,
            // problems arise. There's no definite answer how to solve this, because in some occasions a subquery
            // is indeed desired and in others simply not at all. Here it's not desired, not at all, so this flag
            // is set to prevent that. We cannot simply prevent subquery optimization because a relation is
            // selected. Consider the following filter: servicegroup.name=app-db&servicegroup.name=
            // Put this into the searchbar at icingadb/services and request suggestions for the second condition.
            // You should see suggestions for not only "app-db" but also other servicegroups. This would not be
            // the case if the FilterProcessor wouldn't use a subquery to resolve the servicegroup.name=app-db
            // condition. Here a subquery **is** desired, although the same relation is being selected. There
            // are other cases when a subquery is required, but I won't list them here. This is the simplest
            // example that shows what this flag is about and why it's there. Because when asking for custom
            // variable suggestions, we ask for `flatvalue` cells **and** filter by `flatname`. Having a subquery
            // resolve the filter leads to false-positives.
            $flatnameFilter->noOptimization = true;

            FilterProcessor::apply($flatnameFilter, $query);
        }

        $inputFilter = Filter::equal($columnPath, $searchTerm);
        $inputFilter->noOptimization = true;
        $query->columns($columnPath);

        // This had so many iterations, if it still doesn't work, consider removing it entirely :(
        if ($searchFilter instanceof Filter\None) {
            FilterProcessor::apply($inputFilter, $query);
        } elseif ($searchFilter instanceof Filter\All) {
            $searchFilter->add($inputFilter);
        } else {
            $searchFilter = $inputFilter;
        }

        FilterProcessor::apply($searchFilter, $query);
        $this->applyRestrictions($query);

        try {
            return (new Cursor($query->getDb(), $query->assembleSelect()->distinct()))
                ->setFetchMode(PDO::FETCH_COLUMN);
        } catch (RuntimeException $_) {
            throw new SearchException(sprintf(t('"%s" is not a valid column'), $columnName));
        }
    }

    protected function fetchColumnSuggestions($searchTerm)
    {
        // Ordinary columns first
        foreach (self::collectFilterColumns($this->getModel()) as $columnName => $columnMeta) {
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
                        $this->add(new HtmlElement(
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
    protected function queryCustomvarConfig($searchTerm)
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
        FilterProcessor::apply(Filter::equal('flatname', $searchTerm), $customVars);
        $idColumn = $resolver->qualifyColumnsAndAliases((array) 'id', $customVars->getModel(), false);
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
     * @return \Generator
     */
    public static function collectFilterColumns(Model $model, Resolver $resolver = null)
    {
        if ($resolver === null) {
            $resolver = new Resolver();
        }

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
    }
}
