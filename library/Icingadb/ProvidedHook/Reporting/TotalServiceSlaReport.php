<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\ProvidedHook\Reporting;

use Icinga\Module\Icingadb\Hook\Common\TotalSlaReportUtils;

use function ipl\I18n\t;

class TotalServiceSlaReport extends ServiceSlaReport
{
    use TotalSlaReportUtils;

    public function getName()
    {
        return t('Total Service SLA');
    }
}
