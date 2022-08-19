<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web;

use Exception;
use Generator;
use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Application\Version;
use Icinga\Data\ConfigObject;
use Icinga\Date\DateFormatter;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Common\BaseItemList;
use Icinga\Module\Icingadb\Common\SearchControls;
use Icinga\Module\Icingadb\Data\CsvResultSet;
use Icinga\Module\Icingadb\Data\JsonResultSet;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use Icinga\Module\Icingadb\Widget\ItemTable\BaseItemTable;
use Icinga\Module\Pdfexport\PrintableHtmlDocument;
use Icinga\Module\Pdfexport\ProvidedHook\Pdfexport;
use Icinga\Security\SecurityException;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Util\Environment;
use Icinga\Util\Json;
use ipl\Html\Html;
use ipl\Html\ValidHtml;
use ipl\Orm\Query;
use ipl\Orm\UnionQuery;
use ipl\Stdlib\Contract\Paginatable;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\PaginationControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;

class Controller extends CompatController
{
    use Auth;
    use Database;
    use SearchControls;

    /** @var Filter\Rule Filter from query string parameters */
    private $filter;

    /** @var string|null */
    private $format;

    /** @var bool */
    private $formatProcessed = false;

    /**
     * Get the filter created from query string parameters
     *
     * @return Filter\Rule
     */
    public function getFilter(): Filter\Rule
    {
        if ($this->filter === null) {
            $this->filter = QueryString::parse((string) $this->params);
        }

        return $this->filter;
    }

    public function createColumnControl(Query $query, ViewModeSwitcher $viewModeSwitcher)
    {
        // All of that is essentially what `ColumnControl::apply()` should do
        $columnsDef = $this->params->shift('columns');
        if (! $columnsDef) {
            return null;
        }

        $columns = [];
        foreach (explode(',', $columnsDef) as $column) {
            if ($column = trim($column)) {
                $columns[] = $column;
            }
        }

        $query->withColumns($columns);

        if (! $this->getRequest()->getUrl()->hasParam($viewModeSwitcher->getViewModeParam())) {
            $viewModeSwitcher->setViewMode('tabular');
        }

        // For now this also returns the columns, but they should be accessible
        // by calling `ColumnControl::getColumns()` in the future
        return $columns;
    }

    /**
     * Create and return the LimitControl
     *
     * This automatically shifts the limit URL parameter from {@link $params}.
     *
     * @return LimitControl
     */
    public function createLimitControl(): LimitControl
    {
        $limitControl = new LimitControl(Url::fromRequest());
        $limitControl->setDefaultLimit($this->getPageSize(null));

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
    public function createPaginationControl(Paginatable $paginatable): PaginationControl
    {
        $paginationControl = new PaginationControl($paginatable, Url::fromRequest());
        $paginationControl->setDefaultPageSize($this->getPageSize(null));
        $paginationControl->setAttribute('id', $this->getRequest()->protectId('pagination-control'));

        $this->params->shift($paginationControl->getPageParam());
        $this->params->shift($paginationControl->getPageSizeParam());

        return $paginationControl->apply();
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
    public function createSortControl(Query $query, array $columns): SortControl
    {
        $sortControl = SortControl::create($columns);

        $this->params->shift($sortControl->getSortParam());

        return $sortControl->apply($query);
    }

    /**
     * Create and return the ViewModeSwitcher
     *
     * This automatically shifts the view mode URL parameter from {@link $params}.
     *
     * @param PaginationControl $paginationControl
     * @param LimitControl      $limitControl
     * @param bool              $verticalPagination
     *
     * @return ViewModeSwitcher
     */
    public function createViewModeSwitcher(
        PaginationControl $paginationControl,
        LimitControl $limitControl,
        bool $verticalPagination = false
    ): ViewModeSwitcher {
        $viewModeSwitcher = new ViewModeSwitcher();
        $viewModeSwitcher->setIdProtector([$this->getRequest(), 'protectId']);

        $user = $this->Auth()->getUser();
        if (($preferredModes = $user->getAdditional('icingadb.view_modes')) === null) {
            try {
                $preferredModes = Json::decode(
                    $user->getPreferences()->getValue('icingadb', 'view_modes', '[]'),
                    true
                );
            } catch (JsonDecodeException $e) {
                Logger::error('Failed to load preferred view modes for user "%s": %s', $user->getUsername(), $e);
                $preferredModes = [];
            }

            $user->setAdditional('icingadb.view_modes', $preferredModes);
        }

        $requestRoute = $this->getRequest()->getUrl()->getPath();
        if (isset($preferredModes[$requestRoute])) {
            $viewModeSwitcher->setDefaultViewMode($preferredModes[$requestRoute]);
        }

        $viewModeSwitcher->populate([
            $viewModeSwitcher->getViewModeParam() => $this->params->shift($viewModeSwitcher->getViewModeParam())
        ]);

        $session = $this->Window()->getSessionNamespace(
            'icingadb-viewmode-' . $this->Window()->getContainerId()
        );

        $viewModeSwitcher->on(
            ViewModeSwitcher::ON_SUCCESS,
            function (ViewModeSwitcher $viewModeSwitcher) use (
                $user,
                $preferredModes,
                $paginationControl,
                $verticalPagination,
                &$session
            ) {
                $viewMode = $viewModeSwitcher->getValue($viewModeSwitcher->getViewModeParam());
                $requestUrl = Url::fromRequest();

                $preferredModes[$requestUrl->getPath()] = $viewMode;
                $user->setAdditional('icingadb.view_modes', $preferredModes);

                try {
                    $preferencesStore = PreferencesStore::create(new ConfigObject([
                        //TODO: Don't set store key as it will no longer be needed once we drop support for
                        // lower version of icingaweb2 then v2.11.
                        //https://github.com/Icinga/icingaweb2/pull/4765
                        'store'     => Config::app()->get('global', 'config_backend', 'db'),
                        'resource'  => Config::app()->get('global', 'config_resource')
                    ]), $user);
                    $preferencesStore->load();
                    $preferencesStore->save(
                        new Preferences(['icingadb' => ['view_modes' => Json::encode($preferredModes)]])
                    );
                } catch (Exception $e) {
                    Logger::error('Failed to save preferred view mode for user "%s": %s', $user->getUsername(), $e);
                }

                $pageParam = $paginationControl->getPageParam();
                $limitParam = LimitControl::DEFAULT_LIMIT_PARAM;
                $currentPage = $paginationControl->getCurrentPageNumber();

                $requestUrl->setParam($viewModeSwitcher->getViewModeParam(), $viewMode);
                if (! $requestUrl->hasParam($limitParam)) {
                    if ($viewMode === 'minimal') {
                        $session->set('previous_page', $currentPage);
                        $session->set('request_path', $requestUrl->getPath());

                        $limit = $paginationControl->getLimit();
                        if (! $verticalPagination) {
                            // We are computing it based on the first element being rendered on this current page
                            $currentPage = (int) (floor((($currentPage * $limit) - $limit) / ($limit * 2)) + 1);
                        } else {
                            $currentPage = (int) (round($currentPage * $limit / ($limit * 2)));
                        }

                        $session->set('current_page', $currentPage);
                    } elseif ($viewModeSwitcher->getDefaultViewMode() === 'minimal') {
                        $limit = $paginationControl->getLimit();
                        if ($currentPage === $session->get('current_page')) {
                            // No other page numbers have been selected, i.e the user only
                            // switches back and forth without changing the page numbers
                            $currentPage =  $session->get('previous_page');
                        } elseif (! $verticalPagination) {
                            $currentPage = (int) (floor((($currentPage * $limit) - $limit) / ($limit / 2)) + 1);
                        } else {
                            $currentPage = (int) (floor($currentPage * $limit / ($limit / 2)));
                        }

                        $session->clear();
                    }

                    if (($requestUrl->hasParam($pageParam) && $currentPage > 1) || $currentPage > 1) {
                        $requestUrl->setParam($pageParam, $currentPage);
                    } else {
                        $requestUrl->remove($pageParam);
                    }
                }

                $this->redirectNow($requestUrl);
            }
        )->handleRequest(ServerRequest::fromGlobals());

        if ($viewModeSwitcher->getViewMode() === 'minimal') {
            $hasLimitParam = Url::fromRequest()->hasParam($limitControl->getLimitParam());

            if ($paginationControl->getDefaultPageSize() <= LimitControl::DEFAULT_LIMIT && ! $hasLimitParam) {
                $paginationControl->setDefaultPageSize($paginationControl->getDefaultPageSize() * 2);
                $limitControl->setDefaultLimit($limitControl->getDefaultLimit() * 2);

                $paginationControl->apply();
            }
        }

        $requestPath =  $session->get('request_path');
        if ($requestPath && $requestPath !== $requestRoute) {
            $session->clear();
        }

        return $viewModeSwitcher;
    }

    /**
     * Process a search request
     *
     * @param Query $query
     *
     * @return void
     */
    public function handleSearchRequest(Query $query)
    {
        $q = trim($this->params->shift('q', ''), ' *');
        if (! $q) {
            return;
        }

        $filter = Filter::any();
        foreach ($query->getModel()->getSearchColumns() as $column) {
            $filter->add(Filter::like(
                $query->getResolver()->qualifyColumn($column, $query->getModel()->getTableName()),
                "*$q*"
            ));
        }

        $requestUrl = Url::fromRequest();

        $existingParams = $requestUrl->getParams()->without('q');
        $requestUrl->setQueryString(QueryString::render($filter));
        foreach ($existingParams->toArray(false) as $name => $value) {
            $requestUrl->getParams()->addEncoded($name, $value);
        }

        $this->getResponse()->redirectAndExit($requestUrl);
    }

    /**
     * Require permission to access the given route
     *
     * @param string $name If NULL, the current controller name is used
     *
     * @throws SecurityException
     */
    public function assertRouteAccess(string $name = null)
    {
        if (! $name) {
            $name = $this->getRequest()->getControllerName();
        }

        if (! $this->isPermittedRoute($name)) {
            throw new SecurityException('No permission to access this route');
        }
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

        // It only makes sense to export a single result to CSV or JSON
        $query = $queries[0];

        // No matter the format, a limit should only apply if set
        if ($this->format !== null) {
            $query->limit(Url::fromRequest()->getParam('limit'));
        }

        if ($this->format === 'json' || $this->format === 'csv') {
            $response = $this->getResponse();
            $fileName = $this->view->title;

            ob_end_clean();
            Environment::raiseExecutionTime();

            if ($this->format === 'json') {
                $response
                    ->setHeader('Content-Type', 'application/json')
                    ->setHeader('Cache-Control', 'no-store')
                    ->setHeader(
                        'Content-Disposition',
                        'attachment; filename=' . $fileName . '.json'
                    )
                    ->sendResponse();

                JsonResultSet::stream($query);
            } else {
                $response
                    ->setHeader('Content-Type', 'text/csv')
                    ->setHeader('Cache-Control', 'no-store')
                    ->setHeader(
                        'Content-Disposition',
                        'attachment; filename=' . $fileName . '.csv'
                    )
                    ->sendResponse();

                CsvResultSet::stream($query);
            }
        }

        $this->getTabs()->enableDataExports();
    }

    /**
     * @todo Remove once support for Icinga Web 2 v2.9.x is dropped
     */
    protected function sendAsPdf()
    {
        if (! Icinga::app()->getModuleManager()->has('pdfexport')) {
            throw new ConfigurationError('The pdfexport module is required for exports to PDF');
        }

        if (version_compare(Version::VERSION, '2.10.0', '>=')) {
            parent::sendAsPdf();
            return;
        }

        putenv('ICINGAWEB_EXPORT_FORMAT=pdf');
        Environment::raiseMemoryLimit('512M');
        Environment::raiseExecutionTime(300);

        $time = DateFormatter::formatDateTime(time());

        $doc = (new PrintableHtmlDocument())
            ->setTitle($this->view->title)
            ->setHeader(Html::wantHtml([
                Html::tag('span', ['class' => 'title']),
                Html::tag('time', null, $time)
            ]))
            ->setFooter(Html::wantHtml([
                Html::tag('span', null, [
                    t('Page') . ' ',
                    Html::tag('span', ['class' => 'pageNumber']),
                    ' / ',
                    Html::tag('span', ['class' => 'totalPages'])
                ]),
                Html::tag('p', null, Url::fromRequest()->setParams($this->params))
            ]))
            ->addHtml($this->content);
        $doc->getAttributes()->add('class', 'icinga-module module-icingadb');

        Pdfexport::first()->streamPdfFromHtml($doc, sprintf(
            '%s-%s',
            $this->view->title ?: $this->getRequest()->getActionName(),
            $time
        ));
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
                            $this->formatProcessed = true;
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
        } elseif ($content instanceof BaseItemTable) {
            $this->content->getAttributes()->add('class', 'full-height');
        }

        return parent::addContent($content);
    }

    public function filter(Query $query, Filter\Rule $filter = null): self
    {
        if ($this->format !== 'sql' || $this->hasPermission('config/authentication/roles/show')) {
            $this->applyRestrictions($query);
        }

        if ($query instanceof UnionQuery) {
            foreach ($query->getUnions() as $query) {
                $query->filter($filter ?: $this->getFilter());
            }
        } else {
            $query->filter($filter ?: $this->getFilter());
        }

        return $this;
    }

    public function preDispatch()
    {
        parent::preDispatch();

        $this->format = $this->params->shift('format');
    }

    public function postDispatch()
    {
        if (! $this->formatProcessed && $this->format !== null && $this->format !== 'pdf') {
            // The purpose of this is not only to show that a requested format isn't supported.
            // It's main purpose is to not allow to bypass restrictions with `?format=sql` as
            // it may be possible that an action applies restrictions, but doesn't support any
            // output formats. Since the restrictions are bypassed in method `$this->filter()`
            // for the SQL output format and the actual format processing is part of a different
            // method (`$this->export()`) which needs to be called explicitly by an action,
            // it's otherwise possible for bad individuals to access unrestricted data.
            $this->httpBadRequest(t('This route does not support the requested output format'));
        }

        parent::postDispatch();
    }

    protected function moduleInit()
    {
        Icinga::app()->getFrontController()
            ->getPlugin('Zend_Controller_Plugin_ErrorHandler')
            ->setErrorHandlerModule('icingadb');
    }
}
