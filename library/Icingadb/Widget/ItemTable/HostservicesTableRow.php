<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Widget\Detail\ServiceStatistics;
use ipl\Html\BaseHtmlElement;
use ipl\Stdlib\Filter;

class HostservicesTableRow extends BaseHostservicesItem
{
    use TableRowLayout;

    protected $defaultAttributes = ['class' => 'servicegroup-table-row'];

    /**
     * Create Service statistics cell
     *
     * @return BaseHtmlElement[]
     */
    protected function createStatistics(): array
    {
        $serviceStats = new ServiceStatistics($this->item);

        $serviceStats->setBaseFilter(Filter::equal('host.name', $this->item->name));
        if (isset($this->table) && $this->table->hasBaseFilter()) {
            $serviceStats->setBaseFilter(
                Filter::all($serviceStats->getBaseFilter(), $this->table->getBaseFilter())
            );
        }

        return [$this->createColumn($serviceStats)];
    }
}
