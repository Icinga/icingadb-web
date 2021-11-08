<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Hook\ExtensionHook\ObjectDetailExtensionHook;
use Icinga\Module\Icingadb\Model\Service;

class ServiceDetail extends ObjectDetail
{
    public function __construct(Service $object)
    {
        parent::__construct($object);
    }

    protected function assemble()
    {
        $this->add(ObjectDetailExtensionHook::injectExtensions([
            0   => $this->createPluginOutput(),
            300 => $this->createActions(),
            301 => $this->createNotes(),
            400 => $this->createComments(),
            401 => $this->createDowntimes(),
            500 => $this->createGroups(),
            501 => $this->createNotifications(),
            600 => $this->createCheckStatistics(),
            601 => $this->createPerformanceData(),
            700 => $this->createCustomVars(),
            701 => $this->createFeatureToggles()
        ], $this->createExtensions()));
    }
}
