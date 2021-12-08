<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook\Cube;

use Icinga\Exception\ConfigurationError;
use Icinga\Module\Cube\Hook\IcingadbHostsHook;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\Host;
use ipl\Orm\Query;

class Hosts extends IcingadbHostsHook
{
    use Auth;
    use Database;

    /**
     * @inheritDoc
     *
     * @return Query
     * @throws \Icinga\Exception\ConfigurationError
     */
    public function getHostStateQuery(): Query
    {
        $query = Host::on($this->getDb());
        $query->disableDefaultSort();

        $query->with('state');
        $this->applyRestrictions($query);

        return $query;
    }

    /**
     * Provide a list of all available fact columns
     *
     * This is a key/value array with the key being the fact name / column alias
     *
     * @return string[]
     */
    public function getAvailableFactColumns()
    {
        return [
            'hosts_cnt'  => 'SUM(1)',
            'hosts_up'  => 'SUM(CASE WHEN host_state.soft_state = 0 THEN  1 ELSE 0 END)',
            'hosts_down'  => 'SUM(CASE WHEN host_state.soft_state = 1 THEN  1 ELSE 0 END)',
            'hosts_unhandled_down'  => 'SUM(CASE WHEN host_state.soft_state = 1'
                . ' AND host_state.is_handled = "n" THEN  1 ELSE 0 END)',
            'hosts_unreachable'  => 'SUM(CASE WHEN host_state.is_reachable = "n" THEN 1 ELSE 0 END)',
            'hosts_unhandled_unreachable' => 'SUM(CASE WHEN host_state.is_reachable = "n"'
                . ' AND host_state.is_handled = "n" THEN  1 ELSE 0 END)'
        ];
    }

    /**
     * Return the host name for the provided slices
     *
     * @param array $slices
     * @return array
     * @throws ConfigurationError
     */
    public function getHostNames($slices)
    {
        $query = Host::on($this->getDb());

        foreach ($slices as $dimension => $value) {
            $dimensionJunction = $dimension . '_junction';

            $query->getSelectBase()
                ->join(
                    "host_customvar {$dimensionJunction}",
                    "{$dimensionJunction}.host_id = host.id"
                )
                ->join(
                    "customvar {$dimension}",
                    "{$dimension}.id = {$dimensionJunction}.customvar_id 
                    AND {$dimension}.name = \"{$dimension}\""
                );
        }

        foreach ($slices as $dimension => $value) {
            $query->getSelectBase()
                ->where("{$dimension}.value = '{$value}'");
        }


        $hosts = [];
        foreach ($query as $row) {
            $hosts[] = $row->name;
        }

        return $hosts;
    }

    /**
     * @return \Generator
     * @throws \Icinga\Exception\ConfigurationError
     */
    public function listAvailableDimensions()
    {
        $query = Host::on($this->getDb());

        $this->applyRestrictions($query);

        $query->getSelectBase()
            ->columns('customvar.name as varname')
            ->join('host_customvar', 'host_customvar.host_id = host.id')
            ->join('customvar', 'customvar.id = host_customvar.customvar_id')
            ->groupBy('customvar.name')
            ->orderBy('customvar.name');

        foreach ($query as $row) {
            yield $row->varname;
        }
    }
}
