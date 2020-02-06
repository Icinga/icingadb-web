<?php

namespace Icinga\Module\Icingadb\Widget\Detail;

class HostDetail extends ObjectDetail
{
    protected function assemble()
    {
        $this->add([
            $this->createPluginOutput(),
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
