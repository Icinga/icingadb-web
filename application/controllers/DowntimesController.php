<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ContinueWith;
use Icinga\Module\Icingadb\Widget\DowntimeList;
use Icinga\Module\Icingadb\Widget\ShowMore;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteDowntimesCommandForm;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlString;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;
use ipl\Web\Widget\Icon;

class DowntimesController extends Controller
{
    public function indexAction()
    {
        $this->setTitle(t('Downtimes'));
        $compact = $this->view->compact;

        $db = $this->getDb();

        $downtimes = Downtime::on($db)->with([
            'host',
            'host.state',
            'service',
            'service.host',
            'service.host.state',
            'service.state'
        ]);

        $this->handleSearchRequest($downtimes);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($downtimes);
        $sortControl = $this->createSortControl(
            $downtimes,
            [
                'downtime.is_in_effect, downtime.start_time desc' => t('Is In Effect'),
                'downtime.entry_time'                             => t('Entry Time'),
                'host.display_name, service.display_name'         => t('Host'),
                'service.display_name, host.display_name'         => t('Service'),
                'downtime.author'                                 => t('Author'),
                'downtime.start_time desc'                        => t('Start Time'),
                'downtime.end_time desc'                          => t('End Time'),
                'downtime.scheduled_start_time desc'              => t('Scheduled Start Time'),
                'downtime.scheduled_end_time desc'                => t('Scheduled End Time'),
                'downtime.duration desc'                          => t('Duration')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher();
        $searchBar = $this->createSearchBar($downtimes, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
            $viewModeSwitcher->getViewModeParam()
        ]);

        $this->filter($downtimes, $searchBar->getFilter());

        $downtimes->peekAhead($compact);

        yield $this->export($downtimes);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);
        $this->addControl(new ContinueWith($this->getFilter(), Links::downtimesDetails()));

        $results = $downtimes->execute();

        $this->addContent((new DowntimeList($results))->setViewMode($viewModeSwitcher->getViewMode()));

        if ($compact) {
            $this->addContent(
                (new ShowMore($results, Url::fromRequest()->without(['showCompact', 'limit'])))
                    ->setAttribute('data-base-target', '_next')
                    ->setAttribute('title', sprintf(
                        t('Show all %d downtimes'),
                        $downtimes->count()
                    ))
            );
        }

        if ($searchBar->hasBeenSent()) {
            $viewModeSwitcher->setUrl($searchBar->getRedirectUrl());
            $this->sendMultipartUpdate($viewModeSwitcher);
        }

        $this->setAutorefreshInterval(10);
    }

    public function deleteAction()
    {
        $this->setTitle(t('Cancel Downtimes'));

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
                t('Confirm cancellation of %d downtimes.'),
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
                    ->add([new Icon('trash'), t('Cancel Downtimes')])
                    ->setSeparator(' ')
                    ->render(),
                'title'      => t('Cancel downtimes'),
                'type'       => 'submit'
            ]
        );

        $cancelDowntimesForm->handleRequest();

        $this->addContent(HtmlString::create($cancelDowntimesForm->render()));
    }

    public function detailsAction()
    {
        $this->setTitle(t('Downtimes'));

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

        $this->addControl(new ShowMore(
            $rs,
            Links::downtimes()->setQueryString($this->getFilter()->toQueryString()),
            sprintf(t('Show all %d downtimes'), $downtimes->count())
        ));

        $this->addContent(new ActionLink(
            sprintf(t('Cancel %d downtimes'), $downtimes->count()),
            Links::downtimesDelete()->setQueryString($this->getFilter()->toQueryString()),
            'trash',
            [
                'class'               => 'cancel-button',
                'data-icinga-modal'   => true,
                'data-no-icinga-ajax' => true
            ]
        ));
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(Downtime::class);
        $suggestions->forRequest(ServerRequest::fromGlobals());
        $this->getDocument()->add($suggestions);
    }
}
