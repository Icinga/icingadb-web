<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Model\Environment;
use Icinga\Module\Icingadb\Widget\Detail\HostStatistics;
use Icinga\Module\Icingadb\Widget\Detail\ServiceStatistics;
use ipl\Html\BaseHtmlElement;
use ipl\Stdlib\BaseFilter;
use ipl\Stdlib\Filter;

/**
 * Hostgroup table row of a hostgroup table. Represents one database row.
 *
 * @property Hostgroup $item
 * @property HostgroupTable $table
 */
class TacticallineTableRow extends BaseTacticallineItem
{
    use TableRowLayout;
    use BaseFilter;

    protected $defaultAttributes = ['class' => 'hostgroup-table-row tacticalline-table-row', 'data-base-target' => '_next'];

    /**
     * Create Host and service statistics columns
     *
     * @return BaseHtmlElement[]
     */
    protected function createStatistics(): array
    {
        $hostStats = new HostStatistics($this->item);

        $hostStats->setBaseFilter($this->getBaseFilter());
        if (isset($this->table) && $this->table->hasBaseFilter()) {
            $hostStats->setBaseFilter(
                Filter::all($hostStats->getBaseFilter(), $this->table->getBaseFilter())
            );
       }

        $serviceStats = new ServiceStatistics($this->item);

        $serviceStats->setBaseFilter($this->getBaseFilter());
        if (isset($this->table) && $this->table->hasBaseFilter()) {
            $serviceStats->setBaseFilter(
                Filter::all($serviceStats->getBaseFilter(), $this->table->getBaseFilter())
            );
        }

        return [
            $this->createColumn($hostStats,'tacticalline-table-row-host'),
            $this->createColumn($serviceStats,'tacticalline-table-row-service')
        ];
    }
}
