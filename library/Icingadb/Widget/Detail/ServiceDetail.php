<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

class ServiceDetail extends ObjectDetail
{
    protected function assemble()
    {
        $this->add([
            $this->createPluginOutput(),
            $this->createExtensions(),
            $this->createEvents(),
            $this->createActions(),
            $this->createNotes(),
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
