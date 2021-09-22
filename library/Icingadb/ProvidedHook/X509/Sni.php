<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook\X509;

use Generator;
use Icinga\Data\Filter\Filter;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\X509\Hook\SniHook;
use ipl\Web\Filter\QueryString;

class Sni extends SniHook
{
    use Auth;
    use Database;

    /**
     * @inheritDoc
     */
    public function getHosts(Filter $filter = null): Generator
    {
        $queryHost = Host::on($this->getDb());

        $queryHost->getSelectBase();

        $hostStatusCols = [
            'host_name'      => 'name',
            'host_address'   => 'address',
            'host_address6'  => 'address6'
        ];

        $queryHost = $queryHost->columns($hostStatusCols);

        $this->applyRestrictions($queryHost);

        if ($filter !== null) {
            $queryString = $filter->toQueryString();
            $filterCondition = QueryString::parse($queryString);
            $queryHost->filter($filterCondition);
        }

        $hosts = $this->getdb()->select($queryHost->assembleSelect());

        foreach ($hosts as $host) {
            if (! empty($host->host_address)) {
                yield $host->host_address => $host->host_name;
            }

            if (! empty($host->host_address6)) {
                yield $host->host_address6 => $host->host_name;
            }
        }
    }
}
