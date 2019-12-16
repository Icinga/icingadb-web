<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\DowntimeList;

class DowntimesController extends Controller
{
    public function indexAction()
    {
        $this->setTitle($this->translate('Downtimes'));

        $db = $this->getDb();

        $downtimes = Downtime::on($db)->with([
            'host',
            'host.state',
            'service',
            'service.host',
            'service.host.state',
            'service.state'
        ]);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($downtimes);
        $sortControl = $this->createSortControl(
            $downtimes,
            [
                'downtime.is_in_effect, downtime.start_time desc' => $this->translate('Is In Effect'),
                'downtime.entry_time'                             => $this->translate('Entry Time'),
                'host.display_name, service.display_name'         => $this->translate('Host'),
                'service.display_name, host.display_name'         => $this->translate('Service'),
                'downtime.author'                                 => $this->translate('Author'),
                'downtime.start_time desc'                        => $this->translate('Start Time'),
                'downtime.end_time desc'                          => $this->translate('End Time'),
                'downtime.scheduled_start_time desc'              => $this->translate('Scheduled Start Time'),
                'downtime.scheduled_end_time desc'                => $this->translate('Scheduled End Time'),
                'downtime.duration desc'                          => $this->translate('Duration')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher();
        $filterControl = $this->createFilterControl($downtimes);

        $this->filter($downtimes);

        yield $this->export($downtimes);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($filterControl);

        $this->addContent((new DowntimeList($downtimes))->setViewMode($viewModeSwitcher->getViewMode()));

        $this->setAutorefreshInterval(10);
    }
}
