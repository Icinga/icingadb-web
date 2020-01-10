<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ContinueWith;
use Icinga\Module\Icingadb\Widget\DowntimeList;
use Icinga\Module\Icingadb\Widget\ShowMore;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteDowntimesCommandForm;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlString;
use ipl\Web\Widget\ActionLink;
use ipl\Web\Widget\Icon;

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
        $this->addControl(new ContinueWith($this->getFilter(), Links::downtimesDetails()));

        $this->addContent((new DowntimeList($downtimes))->setViewMode($viewModeSwitcher->getViewMode()));

        $this->setAutorefreshInterval(10);
    }

    public function deleteAction()
    {
        $this->setTitle($this->translate('Cancel Downtimes'));

        $db = $this->getDb();

        $downtimes = Downtime::on($db)->with([
            'host',
            'host.state',
            'service',
            'service.host',
            'service.host.state',
            'service.state'
        ]);

        $this->filter($downtimes);

        $cancelDowntimesForm = (new DeleteDowntimesCommandForm())
            ->addDescription(sprintf(
                $this->translate('Confirm cancellation of %d downtimes.'),
                $downtimes->count()
            ))
            ->setDowntimes($downtimes)
            ->setRedirectUrl(Links::downtimes())
            ->create();

        $cancelDowntimesForm->removeElement('btn_submit');

        $cancelDowntimesForm->addElement(
            'button',
            'btn_submit',
            [
                'class'      => 'cancel-button spinner',
                'decorators' => [
                    'ViewHelper',
                    ['HtmlTag', ['tag' => 'div', 'class' => 'control-group form-controls']]
                ],
                'escape'     => false,
                'ignore'     => true,
                'label'      => (new HtmlDocument())
                    ->add([new Icon('trash'), $this->translate('Cancel Downtimes')])
                    ->setSeparator(' ')
                    ->render(),
                'title'      => $this->translate('Cancel downtimes'),
                'type'       => 'submit'
            ]
        );

        $cancelDowntimesForm->handleRequest();

        $this->addContent(HtmlString::create($cancelDowntimesForm->render()));
    }

    public function detailsAction()
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

        $downtimes->limit(3)->peekAhead();

        $this->filter($downtimes);

        yield $this->export($downtimes);

        $rs = $downtimes->execute();

        $this->addControl((new DowntimeList($rs))->setViewMode('minimal'));

        if ($rs->hasMore()) {
            $this->addControl(new ShowMore(
                $rs,
                Links::downtimes()->setQueryString($this->getFilter()->toQueryString()),
                sprintf($this->translate('Show all %d downtimes'), $downtimes->count())
            ));
        }

        $this->addContent(new ActionLink(
            sprintf($this->translate('Cancel %d downtimes'), $downtimes->count()),
            Links::downtimesDelete()->setQueryString($this->getFilter()->toQueryString()),
            'trash',
            [
                'class'               => 'cancel-button',
                'data-icinga-modal'   => true,
                'data-no-icinga-ajax' => true
            ]
        ));
    }
}
