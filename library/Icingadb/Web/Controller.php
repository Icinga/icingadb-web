<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web;

use Generator;
use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Icinga;
use Icinga\Data\Filter\Filter;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Compat\MonitoringRestrictions;
use Icinga\Module\Icingadb\Compat\UrlMigrator;
use Icinga\Module\Icingadb\Widget\BaseItemList;
use Icinga\Module\Icingadb\Widget\FilterControl;
use Icinga\Module\Icingadb\Widget\ViewModeSwitcher;
use ipl\Html\Html;
use ipl\Html\ValidHtml;
use ipl\Orm\Common\SortUtil;
use ipl\Orm\Compat\FilterProcessor;
use ipl\Orm\Query;
use ipl\Stdlib\Contract\Paginatable;
use ipl\Web\Compat\CompatController;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\PaginationControl;
use ipl\Web\Control\SearchBar;
use ipl\Web\Control\SortControl;
use ipl\Web\Url;

class Controller extends CompatController
{
    use Database;

    /** @var Filter Filter from query string parameters */
    private $filter;

    /** @var string|null */
    private $format;

    /**
     * Get the filter created from query string parameters
     *
     * @return Filter
     */
    public function getFilter()
    {
        if ($this->filter === null) {
            $this->filter = Filter::fromQueryString((string) $this->params);
        }

        return $this->filter;
    }

    /**
     * Create and return the LimitControl
     *
     * This automatically shifts the limit URL parameter from {@link $params}.
     *
     * @return LimitControl
     */
    public function createLimitControl()
    {
        $limitControl = new LimitControl(Url::fromRequest());

        $this->params->shift($limitControl->getLimitParam());

        return $limitControl;
    }

    /**
     * Create and return the PaginationControl
     *
     * This automatically shifts the pagination URL parameters from {@link $params}.
     *
     * @return PaginationControl
     */
    public function createPaginationControl(Paginatable $paginatable)
    {
        $paginationControl = new PaginationControl($paginatable, Url::fromRequest());

        $this->params->shift($paginationControl->getPageParam());
        $this->params->shift($paginationControl->getPageSizeParam());

        return $paginationControl;
    }

    /**
     * Create and return the SortControl
     *
     * This automatically shifts the sort URL parameter from {@link $params}.
     *
     * @param Query $query
     * @param array $columns Possible sort columns as sort string-label pairs
     *
     * @return SortControl
     */
    public function createSortControl(Query $query, array $columns)
    {
        $default = (array) $query->getModel()->getDefaultSort();
        $normalized = [];
        foreach ($columns as $key => $value) {
            $normalized[SortUtil::normalizeSortSpec($key)] = $value;
        }
        $sortControl = (new SortControl(Url::fromRequest()))
            ->setColumns($normalized);

        if (! empty($default)) {
            $sortControl->setDefault(SortUtil::normalizeSortSpec($default));
        }

        $sort = $sortControl->getSort();

        if (! empty($sort)) {
            $query->orderBy(SortUtil::createOrderBy($sort));
        }

        $this->params->shift($sortControl->getSortParam());

        return $sortControl;
    }

    /**
     * Create and return the SearchBar
     *
     * @param array $preserveParams Query params to preserve when redirecting
     *
     * @return SearchBar
     */
    public function createSearchBar(array $preserveParams = null)
    {
        $requestUrl = Url::fromRequest();
        if ($preserveParams !== null) {
            $requestUrl = $requestUrl->onlyWith($preserveParams);
        }

        $searchBar = new SearchBar();
        $searchBar->setSubmitLabel(t('Search'));
        $searchBar->setFilter($this->getFilter());
        $searchBar->setAction($requestUrl->getAbsoluteUrl());
        $searchBar->setIdProtector([$this->getRequest(), 'protectId']);

        if (method_exists($this, 'completeAction')) {
            $searchBar->setSuggestionUrl(Url::fromPath(
                'icingadb/' . $this->getRequest()->getControllerName() . '/complete',
                ['_disableLayout' => true]
            ));
        }

        $searchBar->on(SearchBar::ON_SUCCESS, function (SearchBar $form) use ($requestUrl) {
            $existingParams = $requestUrl->getParams();
            $requestUrl->setQueryString($form->getFilter()->toQueryString());
            foreach ($existingParams->toArray(false) as $name => $value) {
                $requestUrl->getParams()->addEncoded($name, $value);
            }

            $this->getResponse()->redirectAndExit($requestUrl);
        })->handleRequest(ServerRequest::fromGlobals());

        Html::tag('div', ['class' => 'filter'])->wrap($searchBar);

        return $searchBar;
    }

    /**
     * Create and return the FilterControl
     *
     * @param Query $query
     * @param array $preserveParams
     *
     * @return FilterControl
     */
    public function createFilterControl(Query $query, array $preserveParams = null)
    {
        $request = clone $this->getRequest();
        $params = clone $this->params;

        if (! empty($preserveParams)) {
            foreach ($preserveParams as $param) {
                if (! $params->has($param) && ($value = $request->getUrl()->getParam($param)) !== null) {
                    $params->set($param, $value);
                }
            }
        }

        $request->getUrl()->setParams($params);

        $filterControl = new FilterControl($query, $preserveParams);
        $filterControl->handleRequest($request);

        // We're cloning the params, the editor does it, so we have to shift these ourselves
        $this->params->shift('addFilter');
        $this->params->shift('removeFilter');
        $this->params->shift('stripFilter');
        $this->params->shift('modifyFilter');
        $this->params->shift('q');

        return $filterControl;
    }

    /**
     * Create and return the ViewModeSwitcher
     *
     * This automatically shifts the view mode URL parameter from {@link $params}.
     *
     * @return ViewModeSwitcher
     */
    public function createViewModeSwitcher()
    {
        $viewModeSwitcher = new ViewModeSwitcher(Url::fromRequest());

        $this->params->shift($viewModeSwitcher->getViewModeParam());

        return $viewModeSwitcher;
    }

    public function export(Query ...$queries)
    {
        if ($this->format === 'sql') {
            foreach ($queries as $query) {
                list($sql, $values) = $query->getDb()->getQueryBuilder()->assembleSelect($query->assembleSelect());

                $unused = [];
                foreach ($values as $value) {
                    $pos = strpos($sql, '?');
                    if ($pos !== false) {
                        if (is_string($value)) {
                            $value = "'" . $value . "'";
                        }

                        $sql = substr_replace($sql, $value, $pos, 1);
                    } else {
                        $unused[] = $value;
                    }
                }

                if (!empty($unused)) {
                    $sql .= ' /* Unused values: "' . join('", "', $unused) . '" */';
                }

                $this->content->add(Html::tag('pre', $sql));
            }

            return true;
        }

        $this->getTabs()->enableDataExports();
    }

    public function dispatch($action)
    {
        // Notify helpers of action preDispatch state
        $this->_helper->notifyPreDispatch();

        $this->preDispatch();

        if ($this->getRequest()->isDispatched()) {
            // If pre-dispatch hooks introduced a redirect then stop dispatch
            // @see ZF-7496
            if (! $this->getResponse()->isRedirect()) {
                $interceptable = $this->$action();
                if ($interceptable instanceof Generator) {
                    foreach ($interceptable as $stopSignal) {
                        if ($stopSignal === true) {
                            break;
                        }
                    }
                }
            }
            $this->postDispatch();
        }

        // whats actually important here is that this action controller is
        // shutting down, regardless of dispatching; notify the helpers of this
        // state
        $this->_helper->notifyPostDispatch();
    }

    protected function addContent(ValidHtml $content)
    {
        if ($content instanceof BaseItemList) {
            $this->content->getAttributes()->add('class', 'full-width');
        }

        return parent::addContent($content);
    }

    public function filter(Query $query)
    {
        $this->applyMonitoringRestriction($query);

        FilterProcessor::apply(
            $this->getFilter(),
            $query
        );

        return $this;
    }

    public function applyMonitoringRestriction(Query $query, $queryTransformer = null)
    {
        if ($queryTransformer === null || UrlMigrator::hasQueryTransformer($queryTransformer)) {
            $restriction = UrlMigrator::transformFilter(
                MonitoringRestrictions::getRestriction('monitoring/filter/objects'),
                $queryTransformer
            );
            if ($restriction) {
                FilterProcessor::apply($restriction, $query);
            }
        }

        return $this;
    }

    public function preDispatch()
    {
        parent::preDispatch();

        $this->format = $this->params->shift('format');
    }

    protected function moduleInit()
    {
        Icinga::app()->getModuleManager()->loadModule('monitoring');

        Icinga::app()->getFrontController()
            ->getPlugin('Zend_Controller_Plugin_ErrorHandler')
            ->setErrorHandlerModule('icingadb');
    }
}
