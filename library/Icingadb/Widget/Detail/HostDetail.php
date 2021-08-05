<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Module\Icingadb\Widget\HorizontalKeyValue;
use ipl\Html\Html;
use ipl\Stdlib\Filter;

class HostDetail extends ObjectDetail
{
    protected $serviceSummary;

    public function __construct($object, $serviceSummary)
    {
        parent::__construct($object);

        $this->serviceSummary = $serviceSummary;
    }

    protected function createServiceStatistics()
    {
        if ($this->serviceSummary->services_total > 0) {
            $services = new ServiceStatistics($this->serviceSummary);
            $services->setBaseFilter(Filter::equal('host.name', $this->object->name));
        } else {
            $services = new EmptyState(t('This host has no services'));
        }

        return [Html::tag('h2', t('Services')), $services];
    }

    protected function assemble()
    {
        $this->add([
            $this->createPluginOutput(),
            $this->createExtensions(),
            $this->createActions(),
            $this->createNotes(),
            $this->createServiceStatistics(),
            $this->createGroups(),
            $this->createComments(),
            $this->createDowntimes(),
            $this->createNotifications(),
            $this->createCheckStatistics(),
            $this->createPerformanceData(),
            $this->createCustomVars(),
            $this->createFeatureToggles()
        ]);
    }
}
