<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Common\EmbeddedList;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\EventDetail;
use Icinga\Module\Icingadb\Widget\Detail\ObjectHeader;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;

class EventController extends Controller
{
    use EmbeddedList;

    /** @var History */
    protected $event;

    public function init()
    {
        $this->addTitleTab(t('Event'));

        $id = $this->params->shiftRequired('id');

        $query = History::on($this->getDb())
            ->with([
               'host',
               'host.state',
               'service',
               'service.state',
               'comment',
               'downtime',
               'downtime.parent',
               'downtime.parent.host',
               'downtime.parent.host.state',
               'downtime.parent.service',
               'downtime.parent.service.state',
               'downtime.triggered_by',
               'downtime.triggered_by.host',
               'downtime.triggered_by.host.state',
               'downtime.triggered_by.service',
               'downtime.triggered_by.service.state',
               'flapping',
               'notification',
               'acknowledgement',
               'state'
            ])
            ->filter(Filter::equal('id', hex2bin($id)));

        $this->applyRestrictions($query);

        $event = $query->first();
        if ($event === null) {
            $this->httpNotFound(t('Event not found'));
        }

        $this->event = $event;
    }

    public function indexAction()
    {
        $list = null;
        if ($this->event->event_type === 'notification' && $this->isPermittedRoute('contacts')) {
            $users = User::on($this->getDb())
                ->filter(Filter::equal('notification_history.history.id', $this->event->id));
            $this->applyRestrictions($users);

            $list = $this->createEmbeddedList($users, [
                'user.display_name' => $this->translate('Name'),
                'user.email'        => $this->translate('Email'),
                'user.pager'        => $this->translate('Pager Address / Number'),
            ], ['id']);
            if ($list === null) {
                $this->sendMultipartUpdate();

                return;
            }
        }

        $this->addControl(new ObjectHeader($this->event));
        $this->addContent(new EventDetail($this->event, $list));
    }

    /**
     * Show filter suggestions for the notified contacts
     *
     * @return void
     */
    public function completeAction(): void
    {
        $this->assertRouteAccess('contacts');

        if ($this->event->event_type !== 'notification') {
            $this->httpBadRequest($this->translate('This event has no notified contacts'));
        }

        $suggestions = (new ObjectSuggestions())
            ->setModel(User::class)
            ->onlyWithCustomVarSources(['user'])
            ->setBaseFilter(Filter::equal('notification_history.history.id', $this->event->id))
            ->forRequest($this->getServerRequest());

        $this->getDocument()->add($suggestions);
    }

    /**
     * Show the filter editor for the notified contacts
     *
     * @return void
     */
    public function searchEditorAction(): void
    {
        $this->assertRouteAccess('contacts');

        if ($this->event->event_type !== 'notification') {
            $this->httpBadRequest($this->translate('This event has no notified contacts'));
        }

        $editor = $this->createSearchEditor(User::on($this->getDb()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            'id',
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle($this->translate('Adjust Filter'));
    }
}
