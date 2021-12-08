<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook\Cube;

use Icinga\Module\Cube\Hook\IcingadbServicesHook;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Orm\Query;

class Services extends IcingadbServicesHook
{
    use Auth;
    use Database;

    /**
     * @inheritDoc
     *
     * @return Query
     * @throws \Icinga\Exception\ConfigurationError
     */
    public function getServiceStateQuery(): Query
    {
        $query = Service::on($this->getDb())->with('state');
        $query->disableDefaultSort();

        $this->applyRestrictions($query);

        return $query;
    }

    /**
     * @return string[]
     */
    public function getAvailableFactColumns()
    {
        return [
            'services_cnt'           => 'SUM(1)',
            'services_critical'           => 'SUM(CASE WHEN service_state.soft_state = 2 THEN  1 ELSE 0 END)',
            'services_unhandled_critical' => 'SUM(CASE WHEN service_state.soft_state = 2'
                . ' AND service_state.is_handled = "n" THEN  1 ELSE 0 END)',
            'services_warning'           => 'SUM(CASE WHEN service_state.soft_state = 1 THEN  1 ELSE 0 END)',
            'services_unhandled_warning' => 'SUM(CASE WHEN service_state.soft_state = 1'
                . ' AND service_state.is_handled = "n" THEN  1 ELSE 0 END)',
            'services_unknown'           => 'SUM(CASE WHEN service_state.soft_state = 3 THEN  1 ELSE 0 END)',
            'services_unhandled_unknown' => 'SUM(CASE WHEN service_state.soft_state = 3'
                . ' AND service_state.is_handled = "n" THEN  1 ELSE 0 END)',
        ];
    }

    /**
     * @return \Generator
     * @throws \Icinga\Exception\ConfigurationError
     */
    public function listAvailableDimensions()
    {
        $query = Service::on($this->getDb());

        $this->applyRestrictions($query);

        $query->getSelectBase()
            ->columns('customvar.name as varname')
            ->join('service_customvar', 'service_customvar.service_id = service.id')
            ->join('customvar', 'customvar.id = service_customvar.customvar_id')
            ->groupBy('customvar.name')
            ->orderBy('customvar.name');

        foreach ($query as $row) {
            yield $row->varname;
        }
    }
}
