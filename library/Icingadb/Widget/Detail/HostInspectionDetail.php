<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\ObjectInspectionDetail;

class HostInspectionDetail extends ObjectInspectionDetail
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
