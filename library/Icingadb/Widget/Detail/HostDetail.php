<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Hook\ExtensionHook\ObjectDetailExtensionHook;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use ipl\Html\Html;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\EmptyState;

class HostDetail extends ObjectDetail
{
    protected $serviceSummary;

    public function __construct(Host $object, ServicestateSummary $serviceSummary)
    {
        parent::__construct($object);

        $this->serviceSummary = $serviceSummary;
    }

    protected function createServiceStatistics(): array
    {
        if ($this->serviceSummary->services_total > 0) {
            $services = new ServiceStatistics($this->serviceSummary, $this->object);
        } else {
            $services = new EmptyState(t('This host has no services'));
        }

        return [Html::tag('h2', t('Services')), $services];
    }

    protected function assemble()
    {
        if (getenv('ICINGAWEB_EXPORT_FORMAT') === 'pdf') {
            $this->add($this->createPrintHeader());
        }

        $this->add(ObjectDetailExtensionHook::injectExtensions([
            0   => $this->createRootProblems(),
            1   => $this->createPluginOutput(),
            190 => $this->createServiceStatistics(),
            300 => $this->createActions(),
            301 => $this->createNotes(),
            400 => $this->createComments(),
            401 => $this->createDowntimes(),
            500 => $this->createGroups(),
            501 => $this->createNotifications(),
            510 => $this->createAffectedObjects(),
            600 => $this->createCheckStatistics(),
            601 => $this->createPerformanceData(),
            700 => $this->createCustomVars(),
            701 => $this->createFeatureToggles()
        ], $this->createExtensions()));
    }
}
