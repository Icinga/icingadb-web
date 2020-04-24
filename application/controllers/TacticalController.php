<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\HoststateSummary;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\HostSummaryDonut;
use Icinga\Module\Icingadb\Widget\ServiceSummaryDonut;

class TacticalController extends Controller
{
    public function indexAction()
    {
        $this->setTitle(t('Tactical Overview'));

        $db = $this->getDb();

        $hoststateSummary = HoststateSummary::on($db)->with('state');
        // With relation `host` because otherwise the filter editor only presents service cols
        $servicestateSummary = ServicestateSummary::on($db)->with(['state', 'host']);

        $filterControl = $this->createFilterControl($servicestateSummary);
        $this->createFilterControl($hoststateSummary);

        $this->filter($hoststateSummary);
        $this->filter($servicestateSummary);

        yield $this->export($hoststateSummary, $servicestateSummary);

        $this->addControl($filterControl);

        $this->addContent(
            (new HostSummaryDonut($hoststateSummary->first()))
                ->setBaseFilter($this->getFilter())
        );

        $this->addContent(
            (new ServiceSummaryDonut($servicestateSummary->first()))
                ->setBaseFilter($this->getFilter())
        );

        $this->setAutorefreshInterval(10);
    }
}
