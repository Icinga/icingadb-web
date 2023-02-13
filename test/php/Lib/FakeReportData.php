<?php

namespace Tests\Icinga\Module\Icingadb\Lib;

use Icinga\Module\Icingadb\ProvidedHook\Reporting\Common\SlaTimelines;

class FakeReportData
{
    use SlaTimelines;

    public function setDimensions(array $_)
    {
    }

    public function getDimensions()
    {
        return [];
    }

    public function setRows(array $rows)
    {
    }
}
