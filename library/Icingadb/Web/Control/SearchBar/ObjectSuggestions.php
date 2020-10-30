<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Control\SearchBar;

use Icinga\Data\Filter\Filter;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\CustomvarFlat;
use ipl\Html\HtmlElement;
use ipl\Orm\Compat\FilterProcessor;
use ipl\Orm\Model;
use ipl\Orm\Relation\BelongsToMany;
use ipl\Orm\Resolver;
use ipl\Sql\Cursor;
use ipl\Sql\Expression;
use ipl\Sql\Select;
use ipl\Stdlib\Filter as StdlibFilter;
use ipl\Web\Control\SearchBar\Suggestions;
use PDO;

class ObjectSuggestions extends Suggestions
{
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

        $quickFilter = StdlibFilter::any();
        foreach ($model->getSearchColumns() as $column) {
            $where = StdlibFilter::equal($model->getTableName() . '.' . $column, $searchTerm);
            $where->columnLabel = $model->getMetaData()[$column];
            $quickFilter->add($where);
        }

        return $quickFilter;
    }

    /**
     * @todo Don't suggest obfuscated (protected) custom variables
     */
    protected function fetchValueSuggestions($column, $searchTerm)
    {
        $model = $this->getModel();
        $query = $model::on($this->getDb());

        $columnPath = $query->getResolver()->qualifyPath($column, $model->getTableName());
        list($targetPath, $columnName) = preg_split('/(?<=vars)\.|\.(?=[^.]+$)/', $columnPath);

        if (strpos($targetPath, '.') !== false) {
            $target = $query->getResolver()->resolveRelation($targetPath)->getTarget();
            if ($target instanceof CustomvarFlat) {
                $query = $target::on($this->getDb())->columns('flatvalue');
                FilterProcessor::apply(Filter::where('flatname', $columnName), $query);
                $columnName = 'flatvalue';
            } else {
                $query = $target::on($this->getDb())->columns($columnName);
            }
        } else {
            $query->columns($columnName);
        }

        if (trim($searchTerm, ' *')) {
            FilterProcessor::apply(Filter::where($columnName, $searchTerm), $query);
        }

        return (new Cursor($query->getDb(), $query->assembleSelect()->distinct()))
            ->setFetchMode(PDO::FETCH_COLUMN);
    }

    /**
     * @todo Don't suggest blacklisted custom variables
     */
    protected function fetchColumnSuggestions($searchTerm)
    {
        // Ordinary columns first
        $metaData = (new Resolver())->getMetaData($this->getModel());
        foreach ($metaData as $columnName => $columnMeta) {
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

        $columns = ['flatname'];
        foreach ($customVars->getResolver()->getRelations($customVars->getModel()) as $name => $relation) {
            if (isset($this->customVarSources[$name]) && $relation instanceof BelongsToMany) {
                $junction = $relation->getThrough();

                $foreignKey = $customVars->getResolver()->qualifyColumn(
                    $relation->getForeignKey(),
                    $junction->getTableName()
                );
                $candidateKey = $customVars->getResolver()->qualifyColumn(
                    $relation->getCandidateKey(),
                    $customVars->getModel()->getTableName()
                );

                $columns[$name] = $junction::on($customVars->getDb())->assembleSelect()
                    ->resetColumns()->columns(new Expression('1'))
                    ->where("$foreignKey = $candidateKey")
                    ->limit(1);
            }
        }

        FilterProcessor::apply(Filter::where('flatname', $searchTerm), $customVars);
        $customVars = $customVars->assembleSelect();

        $customVars->resetColumns()->columns($columns);
        $customVars->groupBy('flatname');
        $customVars->limit(static::DEFAULT_LIMIT);

        return $customVars;
    }
}
