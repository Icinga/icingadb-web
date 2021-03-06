<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web;

use Generator;
use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Icinga;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Widget\BaseItemList;
use Icinga\Module\Icingadb\Widget\ViewModeSwitcher;
use InvalidArgumentException;
use ipl\Html\Html;
use ipl\Html\ValidHtml;
use ipl\Orm\Common\SortUtil;
use ipl\Orm\Compat\FilterProcessor;
use ipl\Orm\Query;
use ipl\Stdlib\Contract\Paginatable;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\PaginationControl;
use ipl\Web\Control\SearchBar;
use ipl\Web\Control\SortControl;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;

class Controller extends CompatController
{
    use Auth;
    use Database;

    /** @var Filter Filter from query string parameters */
    private $filter;

    /** @var string|null */
    private $format;

    /**
     * Get the filter created from query string parameters
     *
     * @return Filter\Rule
     */
    public function getFilter()
    {
        if ($this->filter === null) {
            $this->filter = QueryString::parse((string) $this->params);
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
        $paginationControl->setAttribute('id', $this->getRequest()->protectId('pagination-control'));

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
    public function createSearchBar(Query $query, array $preserveParams = null)
    {
        $requestUrl = Url::fromRequest();
        $redirectUrl = $preserveParams !== null ? $requestUrl->onlyWith($preserveParams) : $requestUrl;

        $filter = QueryString::fromString((string) $this->params)
            ->on(QueryString::ON_CONDITION, function (Filter\Condition $condition) use ($query) {
                $path = $condition->getColumn();
                if (strpos($path, '.') === false) {
                    $path = $query->getResolver()->qualifyPath($path, $query->getModel()->getTableName());
                    $condition->setColumn($path);
                }

                if (strpos($path, '.vars.') !== false) {
                    list($target, $varName) = explode('.vars.', $path);
                    if (strpos($target, '.') === false) {
                        // Programmatically translated since the full definition is available in class ObjectSuggestions
                        $condition->columnLabel = sprintf(t(ucfirst($target) . ' %s', '..<customvar-name>'), $varName);
                    }
                } else {
                    $metaData = iterator_to_array(
                        ObjectSuggestions::collectFilterColumns($query->getModel(), $query->getResolver())
                    );
                    if (isset($metaData[$path])) {
                        $condition->columnLabel = $metaData[$path];
                    }
                }
            })
            ->parse();

        $searchBar = new SearchBar();
        $searchBar->setSubmitLabel(t('Search'));
        $searchBar->setFilter($filter);
        $searchBar->setAction($requestUrl->getAbsoluteUrl());
        $searchBar->setIdProtector([$this->getRequest(), 'protectId']);

        if (method_exists($this, 'completeAction')) {
            $searchBar->setSuggestionUrl(Url::fromPath(
                'icingadb/' . $this->getRequest()->getControllerName() . '/complete',
                ['_disableLayout' => true, 'showCompact' => true]
            ));
        }

        $searchBar->on(SearchBar::ON_CHANGE, function (array &$changes) use ($query) {
            if ($changes['type'] === 'remove') {
                return;
            }

            $metaData = iterator_to_array(
                ObjectSuggestions::collectFilterColumns($query->getModel(), $query->getResolver())
            );
            foreach ($changes['terms'] as &$termData) {
                if (($pos = strpos($termData['search'], '.vars.')) !== false) {
                    try {
                        $query->getResolver()->resolveRelation(substr($termData['search'], 0, $pos + 5));
                    } catch (InvalidArgumentException $_) {
                        $termData['invalidMsg'] = sprintf(
                            t('"%s" is not a valid relation'),
                            substr($termData['search'], 0, $pos)
                        );
                    }
                } elseif ($termData['type'] === 'column') {
                    $column = $termData['search'];
                    if (strpos($column, '.') === false) {
                        $column = $query->getResolver()->qualifyPath($column, $query->getModel()->getTableName());
                        // TODO: Also apply this change, though not until.. (see below)
                    }

                    if (! isset($metaData[$column])) {
                        // TODO: Enable once https://github.com/Icinga/ipl-stdlib/issues/17 is done and
                        //       used by the search bar so that not every "change" makes it invalid
                        $path = false; //array_search($column, $metaData, true);
                        if ($path === false) {
                            $termData['invalidMsg'] = t('Is not a valid column');
                        } else {
                            $termData['search'] = $path;
                        }
                    }
                }
            }
        })->on(SearchBar::ON_SENT, function (SearchBar $form) use ($redirectUrl) {
            $existingParams = $redirectUrl->getParams();
            $redirectUrl->setQueryString(QueryString::render($form->getFilter()));
            foreach ($existingParams->toArray(false) as $name => $value) {
                if (is_int($name)) {
                    $name = $value;
                    $value = true;
                }

                $redirectUrl->getParams()->addEncoded($name, $value);
            }

            $form->setRedirectUrl($redirectUrl);
        })->on(SearchBar::ON_SUCCESS, function (SearchBar $form) {
            $this->getResponse()->redirectAndExit($form->getRedirectUrl());
        })->handleRequest(ServerRequest::fromGlobals());

        Html::tag('div', ['class' => 'filter'])->wrap($searchBar);

        return $searchBar;
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
        $viewModeSwitcher->setAttribute('id', $this->getRequest()->protectId('view-switcher'));

        $this->params->shift($viewModeSwitcher->getViewModeParam());

        return $viewModeSwitcher;
    }

    /**
     * Process a search request
     *
     * @param Query $query
     */
    public function handleSearchRequest(Query $query)
    {
        $q = trim($this->params->shift('q', ''), ' *');
        if (! $q) {
            return;
        }

        $filter = Filter::any();
        foreach ($query->getModel()->getSearchColumns() as $column) {
            $filter->add(Filter::equal($column, "*$q*"));
        }

        $requestUrl = Url::fromRequest();

        $existingParams = $requestUrl->getParams()->without('q');
        $requestUrl->setQueryString(QueryString::render($filter));
        foreach ($existingParams->toArray(false) as $name => $value) {
            $requestUrl->getParams()->addEncoded($name, $value);
        }

        $this->getResponse()->redirectAndExit($requestUrl);
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

    public function filter(Query $query, Filter\Rule $filter = null)
    {
        $this->applyRestrictions($query);

        FilterProcessor::apply($filter ?: $this->getFilter(), $query);

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
