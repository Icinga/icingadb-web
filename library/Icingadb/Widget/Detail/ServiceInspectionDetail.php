<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\ObjectInspectionDetail;

class ServiceInspectionDetail extends ObjectInspectionDetail
{
    protected function assemble()
    {
        $this->add([
            $this->createSourceLocation(),
            $this->createLastCheckResult(),
            $this->createAttributes(),
            $this->createCustomVariables(),
            $this->createRedisInfo()
        ]);
    }
}
