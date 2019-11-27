<?php

namespace Icinga\Module\Icingadb\Web;

use Generator;
use Icinga\Application\Icinga;
use Icinga\Data\Filter\Filter;
use Icinga\Data\ResourceFactory;
use Icinga\Module\Icingadb\Widget\FilterControl;
use Icinga\Module\Icingadb\Widget\ViewModeSwitcher;
use ipl\Html\Html;
use ipl\Orm\Compat\FilterProcessor;
use ipl\Orm\Query;
use ipl\Sql\Connection;
use ipl\Stdlib\Contract\PaginationInterface;
use ipl\Web\Compat\CompatController;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\PaginationControl;
use ipl\Web\Url;
use PDO;

class Controller extends CompatController
{
    /** @var Connection Connection to the Icinga database */
    private $db;

    /** @var string|null */
    private $format;

    /** @var \Redis Connection to the Icinga Redis */
    private $redis;

    /**
     * Get the connection to the Icinga database
     *
     * @return Connection
     *
     * @throws \Icinga\Exception\ConfigurationError If the related resource configuration does not exist
     */
    public function getDb()
    {
        if ($this->db === null) {
            $config = ResourceFactory::getResourceConfig($this->Config()->get('icingadb', 'resource', 'icingadb'));

            $config->options = [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE"
                    . ",ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'"
            ];

            $this->db = new Connection($config);
        }

        return $this->db;
    }

    /**
     * Get the connection to the Icinga Redis
     *
     * @return \Redis
     */
    public function getRedis()
    {
        if ($this->redis === null) {
            $config = $this->Config()->getSection('redis');

            $this->redis = new \Redis();
            $this->redis->connect(
                $config->get('host', 'redis'),
                $config->get('port', 6379)
            );
        }

        return $this->redis;
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
    public function createPaginationControl(PaginationInterface $paginatable)
    {
        $paginationControl = new PaginationControl($paginatable, Url::fromRequest());

        $this->params->shift($paginationControl->getPageParam());
        $this->params->shift($paginationControl->getPageSizeParam());

        return $paginationControl;
    }

    /**
     * Create and return the FilterControl
     *
     * @param Query $query
     * @return FilterControl
     */
    public function createFilterControl(Query $query)
    {
        $request = clone $this->getRequest();
        $request->getUrl()->setParams($this->params);

        $filterControl = new FilterControl($query);
        $filterControl->handleRequest($request);

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
                        $sql = substr_replace($sql, "\"{$value}\"", $pos, 1);
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

    public function filter(Query $query)
    {
        FilterProcessor::apply(
            Filter::fromQueryString((string) $this->params),
            $query
        );

        return $this;
    }

    public function preDispatch()
    {
        $this->format = $this->params->shift('format');
    }

    protected function moduleInit()
    {
        Icinga::app()->getModuleManager()->loadModule('monitoring');
    }
}
