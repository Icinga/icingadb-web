<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Forms\Command\Object\DeleteDowntimeForm;
use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use Icinga\Module\Icingadb\Widget\ItemList\ObjectList;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Url;

class DowntimesController extends Controller
{
    use CommandActions;

    public function indexAction()
    {
        $this->addTitleTab(t('Downtimes'));
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

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($downtimes);
        $sortControl = $this->createSortControl(
            $downtimes,
            [
                'downtime.is_in_effect desc, downtime.start_time desc' => t('Is In Effect'),
                'downtime.entry_time'                                  => t('Entry Time'),
                'host.display_name'                                    => t('Host'),
                'service.display_name'                                 => t('Service'),
                'downtime.author'                                      => t('Author'),
                'downtime.start_time desc'                             => t('Start Time'),
                'downtime.end_time desc'                               => t('End Time'),
                'downtime.scheduled_start_time desc'                   => t('Scheduled Start Time'),
                'downtime.scheduled_end_time desc'                     => t('Scheduled End Time'),
                'downtime.duration desc'                               => t('Duration')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);
        $searchBar = $this->createSearchBar($downtimes, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
            $viewModeSwitcher->getViewModeParam()
        ]);

        if ($searchBar->hasBeenSent() && ! $searchBar->isValid()) {
            if ($searchBar->hasBeenSubmitted()) {
                $filter = $this->getFilter();
            } else {
                $this->addControl($searchBar);
                $this->sendMultipartUpdate();

                return;
            }
        } else {
            $filter = $searchBar->getFilter();
        }

        $this->filter($downtimes, $filter);

        $downtimes->peekAhead($compact);

        yield $this->export($downtimes);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);
        $continueWith = $this->createContinueWith(Links::downtimesDetails(), $searchBar);

        $results = $downtimes->execute();

        $this->addContent(
            (new ObjectList($results))
                ->setViewMode($viewModeSwitcher->getViewMode())
                ->setEmptyStateMessage($paginationControl->getEmptyStateMessage())
        );

        if ($compact) {
            $this->addContent(
                (new ShowMore($results, Url::fromRequest()->without(['showCompact', 'limit', 'view'])))
                    ->setBaseTarget('_next')
                    ->setAttribute('title', sprintf(
                        t('Show all %d downtimes'),
                        $downtimes->count()
                    ))
            );
        }

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate($continueWith);
        }

        $this->setAutorefreshInterval(10);
    }

    public function deleteAction()
    {
        $this->assertIsGrantedOnCommandTargets('icingadb/command/downtime/delete');
        $this->setTitle(t('Cancel Downtimes'));
        $this->handleCommandForm(DeleteDowntimeForm::class);
    }

    public function detailsAction()
    {
        $this->addTitleTab(t('Downtimes'));

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

        $this->addControl(
            (new ObjectList($rs))
                ->setViewMode('minimal')
                ->setDetailActionsDisabled()
        );

        $this->addControl(new ShowMore(
            $rs,
            Links::downtimes()->setFilter($this->getFilter()),
            sprintf(t('Show all %d downtimes'), $downtimes->count())
        ));

        $this->addContent(
            (new DeleteDowntimeForm())
                ->setObjects($downtimes)
                ->setAction(
                    Links::downtimesDelete()
                        ->setFilter($this->getFilter())
                        ->getAbsoluteUrl()
                )
        );
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(Downtime::class);
        $suggestions->forRequest(ServerRequest::fromGlobals());
        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction()
    {
        $editor = $this->createSearchEditor(Downtime::on($this->getDb()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }

    protected function getCommandTargetsUrl(): Url
    {
        return Url::fromPath('__CLOSE__');
    }

    protected function fetchCommandTargets(): Query
    {
        $downtimes = Downtime::on($this->getDb())->with([
            'host',
            'host.state',
            'service',
            'service.host',
            'service.host.state',
            'service.state'
        ]);

        $this->filter($downtimes);

        return $downtimes;
    }

    public function isGrantedOn(string $permission, Model $object): bool
    {
        if ($object->scheduled_by !== null) {
            return false;
        }

        return parent::isGrantedOn($permission, $object->{$object->object_type});
    }

    public function isGrantedOnType(string $permission, string $type, Filter\Rule $filter, bool $cache = true): bool
    {
        return parent::isGrantedOnType($permission, 'host', $filter, $cache)
            || parent::isGrantedOnType($permission, 'service', $filter, $cache);
    }
}
