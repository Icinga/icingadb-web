<?php

// SPDX-FileCopyrightText: 2020 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use Icinga\Module\Icingadb\Model\TacticalStateSummary;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\HostSummaryDonut;
use Icinga\Module\Icingadb\Widget\ServiceSummaryDonut;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;

class TacticalController extends Controller
{
    public function indexAction()
    {
        $this->addTitleTab(t('Tactical Overview'));

        $db = $this->getDb();

        $tacticalSummary = TacticalStateSummary::on($db);

        $this->handleSearchRequest($tacticalSummary, [
            'host.name_ci',
            'host.display_name',
            'host.address',
            'host.address6'
        ]);

        $searchBar = $this->createSearchBar($tacticalSummary);
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

        $this->filter($tacticalSummary, $filter);

        yield $this->export($tacticalSummary);

        $this->addControl($searchBar);

        foreach ($tacticalSummary as $row) {
            // The query is a union and always yields host state results first
            if ($row->type === "host_state") {
                $this->addContent(
                    (new HostSummaryDonut($row))
                        ->setBaseFilter($filter)
                );
            } elseif ($row->type === "service_state") {
                $this->addContent(
                    (new ServiceSummaryDonut($row))
                        ->setBaseFilter($filter)
                );
            }
        }

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }

        $this->setAutorefreshInterval(10);
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(ServicestateSummary::class);
        $suggestions->forRequest(ServerRequest::fromGlobals());
        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction()
    {
        $editor = $this->createSearchEditor(ServicestateSummary::on($this->getDb()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }
}
