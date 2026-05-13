<?php

// SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Web\Control\SearchBar;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Data\QueryColumnsProvider;
use Icinga\Module\Icingadb\Data\QueryValuesProvider;
use ipl\Html\HtmlElement;
use ipl\Orm\Model;
use ipl\Stdlib\BaseFilter;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Seq;
use ipl\Web\Control\SearchBar\Suggestions;

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
            'usergroup'             => t('Contactgroup %s', '..<customvar-name>')
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
        return QueryColumnsProvider::shouldShowRelationFor($column, $this->getModel());
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

        if (str_contains($column, ' ')) {
            // $column may be a label
            [$path, $_] = Seq::find(
                $this->fixedColumns
                ?? QueryColumnsProvider::collectFilterColumns($query->getModel(), $query->getResolver()),
                $column,
                false
            );
            if ($path !== null) {
                $column = $path;
            }
        }

        $provider = new QueryValuesProvider($query, $column, $searchTerm, $searchFilter);

        if ($this->hasBaseFilter()) {
            $provider->setBaseFilter($this->getBaseFilter());
        }

        return $provider;
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
