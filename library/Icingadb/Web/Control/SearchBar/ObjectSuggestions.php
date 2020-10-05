<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Control\SearchBar;

use Icinga\Data\Filter\Filter;
use Icinga\Module\Icingadb\Common\Database;
use ipl\Orm\Compat\FilterProcessor;
use ipl\Orm\Resolver;
use ipl\Sql\Cursor;
use ipl\Web\Control\SearchBar\Suggestions;
use PDO;

class ObjectSuggestions extends Suggestions
{
    use Database;

    /** @var string */
    protected $model;

    /**
     * Set the model to show suggestions for
     *
     * @param string $model
     *
     * @return $this
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get the model to show suggestions for
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    protected function createQuickSearchFilter($searchTerm)
    {
        $model = $this->getModel();
        $table = new $model();

        $quickFilter = Filter::matchAny();
        foreach ($table->getSearchColumns() as $column) {
            $where = Filter::where($table->getTableName() . '.' . $column, $searchTerm);
            $where->metaData['label'] = $table->getMetaData()[$column];
            $quickFilter->addFilter($where);
        }

        return $quickFilter;
    }

    protected function fetchValueSuggestions($column, $searchTerm)
    {
        $model = $this->getModel();
        $data = $model::on($this->getDb())->columns($column);
        FilterProcessor::apply(Filter::where($column, $searchTerm), $data);
        $data = new Cursor($data->getDb(), $data->assembleSelect()->distinct());
        $data->setFetchMode(PDO::FETCH_COLUMN);

        return $data;
    }

    protected function fetchColumnSuggestions()
    {
        $model = $this->getModel();

        return (new Resolver())->getMetaData(new $model());
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
}
