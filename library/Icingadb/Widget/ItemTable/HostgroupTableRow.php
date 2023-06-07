<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Model\Hostgroup;
use Icinga\Module\Icingadb\Widget\Detail\HostStatistics;
use Icinga\Module\Icingadb\Widget\Detail\ServiceStatistics;
use ipl\Html\BaseHtmlElement;
use ipl\Stdlib\Filter;

/**
 * Hostgroup table row of a hostgroup table. Represents one database row.
 *
 * @property Hostgroup $item
 * @property HostgroupTable $table
 */
class HostgroupTableRow extends BaseHostGroupItem
{
    use TableRowLayout;

    protected $defaultAttributes = ['class' => 'hostgroup-table-row'];

    /**
     * Create Host and service statistics columns
     *
     * @return BaseHtmlElement[]
     */
    protected function createStatistics(): array
    {
        $hostStats = new HostStatistics($this->item);

        $hostStats->setBaseFilter(Filter::equal('hostgroup.name', $this->item->name));
        if (isset($this->table) && $this->table->hasBaseFilter()) {
            $hostStats->setBaseFilter(
                Filter::all($hostStats->getBaseFilter(), $this->table->getBaseFilter())
            );
        }

        $serviceStats = new ServiceStatistics($this->item);

        $serviceStats->setBaseFilter(Filter::equal('hostgroup.name', $this->item->name));
        if (isset($this->table) && $this->table->hasBaseFilter()) {
            $serviceStats->setBaseFilter(
                Filter::all($serviceStats->getBaseFilter(), $this->table->getBaseFilter())
            );
        }

        return [
            $this->createColumn($hostStats),
            $this->createColumn($serviceStats)
        ];
    }
}
