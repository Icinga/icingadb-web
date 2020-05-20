<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Web\Request;
use Icinga\Web\Widget\FilterEditor;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlString;
use ipl\Orm\Query;

class FilterControl extends HtmlDocument
{
    /** @var Query */
    protected $query;

    /** @var FilterEditor */
    protected $filterEditor;

    /** @var array */
    protected $preserveParams;

    /**
     * FilterControl constructor.
     * @param Query $query
     * @param array $preserveParams
     */
    public function __construct(Query $query, array $preserveParams = null)
    {
        $this->query = $query;
        $this->preserveParams = $preserveParams;
    }

    /**
     * @param Request $request
     */
    public function handleRequest(Request $request)
    {
        $this->getFilterEditor()->handleRequest($request);

        // The editor clones the url prior shifting these so we have to do it again here
        $params = $request->getUrl()->getParams();
        $params->shift('addFilter');
        $params->shift('removeFilter');
        $params->shift('stripFilter');
        $params->shift('modifyFilter');
        $params->shift('q');
    }

    /**
     * @return FilterEditor
     */
    protected function getFilterEditor()
    {
        if ($this->filterEditor === null) {
            $model = $this->query->getModel();
            $columns = $this->selectColumns(
                $this->query->getResolver()->getSelectableColumns($model),
                [$model->getTableName()]
            );
            $searchColumns = array_keys(
                $this->selectColumns($model->getSearchColumns(), [$model->getTableName()])
            );

            foreach ($this->query->getWith() as $path => $relation) {
                $path = explode('.', $path);
                $columns += $this->selectColumns($relation->getTarget()->getColumns(), $path);
                $relationSearchColumns = $relation->getTarget()->getSearchColumns();
                if (! empty($relationSearchColumns)) {
                    array_push($searchColumns, ...array_keys(
                        $this->selectColumns($relationSearchColumns, $path)
                    ));
                }
            }

            $this->filterEditor = (new FilterEditor([]))
                ->setSearchColumns($searchColumns)
                ->setColumns($columns);

            if (! empty($this->preserveParams)) {
                $this->filterEditor->preserveParams(...$this->preserveParams);
            }
        }

        return $this->filterEditor;
    }

    /**
     * @param array $columns
     * @param array $path
     * @return array
     */
    protected function selectColumns(array $columns, array $path = [])
    {
        $titlePath = [];
        if (! empty($path)) {
            $titlePath = array_filter($path, function ($v) {
                return $v !== 'state';
            });
            if (count($titlePath) > 1 && $titlePath[1] === 'host' && $titlePath[0] === 'service') {
                array_shift($titlePath);
            }
        }

        $options = [];
        foreach ($columns as $column) {
            $options[join('.', array_merge($path, [$column]))] = ucwords(
                join(' ', array_merge($titlePath, [str_replace('_', ' ', $column)]))
            );
        }

        return $options;
    }

    protected function assemble()
    {
        $this->add(new HtmlString($this->getFilterEditor()->render()));
    }
}
