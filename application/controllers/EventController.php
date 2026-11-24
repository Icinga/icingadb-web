<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Common\Backend;
use Icinga\Module\Icingadb\Common\EmbeddedList;
use Icinga\Module\Icingadb\Model\AlertHistory;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\EventDetail;
use Icinga\Module\Icingadb\Widget\Detail\ObjectHeader;
use Icinga\Module\Icingadb\Widget\ItemList\AlertList;
use ipl\Orm\Query;
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

            $list = $this->createEmbeddedList($users, $this->translate('Notified Contacts'), [
                'user.display_name' => $this->translate('Name'),
                'user.email'        => $this->translate('Email'),
                'user.pager'        => $this->translate('Pager Address / Number'),
            ], ['id']);
            if ($list === null) {
                $this->sendMultipartUpdate();

                return;
            }
        }

        if (
            $this->event->event_type !== 'notification'
            && Backend::supportsNotifications()
            && $this->event->alert_count > 0
            && $this->getAuth()->hasPermission('icingadb/notifications/usage')
        ) {
            $query = $this->createAlertsQuery();
            $alertList = (new AlertList($query, referenceTime: $this->event->event_time))
                ->addAttributes(['class' => 'alert-list'])
                ->setViewMode('common');
            if ((clone $query)->limit(1)->first() === null) {
                $alertList->setEmptyStateMessage(
                    $this->translate('You have insufficient privileges to see all alerts.'),
                );
            }

            $list = $this->createEmbeddedList($query, $this->translate('Alerts'), [
                'alert_history.triggered_at asc'  => $this->translate('Triggered At'),
                'alert_history.contact_name'      => $this->translate('Contact Name'),
                'alert_history.contactgroup_name' => $this->translate('Contact Group Name'),
                'alert_history.channel_name'      => $this->translate('Channel Name'),
                'alert_history.schedule_name'     => $this->translate('Schedule Name'),
            ], ['id'], list: $alertList, defaultSort: 'alert_history.triggered_at asc');
            if ($list === null) {
                $this->sendMultipartUpdate();

                return;
            }
        }

        $this->addControl(new ObjectHeader($this->event));
        $this->addContent(new EventDetail($this->event, $list));
    }

    /**
     * Show filter suggestions for the event recipients or alerts
     *
     * @return void
     */
    public function completeAction(): void
    {
        $suggestions = new ObjectSuggestions();
        if ($this->event->event_type === 'notification') {
            $this->assertRouteAccess('contacts');
            $suggestions
                ->setModel(User::class)
                ->onlyWithCustomVarSources(['user'])
                ->setBaseFilter(Filter::equal('notification_history.history.id', $this->event->id));
        } else {
            $this->assertPermission('icingadb/notifications/usage');
            $query = $this->createAlertsQuery();
            $suggestions
                ->setModel(AlertHistory::class)
                // Custom variable name sources cannot apply the history scope.
                ->onlyWithCustomVarSources([])
                ->setBaseFilter($query->getFilter());
        }

        $suggestions->forRequest($this->getServerRequest());

        $this->getDocument()->add($suggestions);
    }

    /**
     * Show the filter editor for the event recipients or alerts
     *
     * @return void
     */
    public function searchEditorAction(): void
    {
        if ($this->event->event_type === 'notification') {
            $this->assertRouteAccess('contacts');
            $query = User::on($this->getDb())
                ->filter(Filter::equal('notification_history.history.id', $this->event->id));
            $this->applyRestrictions($query);
        } else {
            $this->assertPermission('icingadb/notifications/usage');
            $query = $this->createAlertsQuery();
        }

        $editor = $this->createSearchEditor($query, [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            'id',
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle($this->translate('Adjust Filter'));
    }

    /**
     * Create the event's alert query with access restrictions
     *
     * @return Query<AlertHistory>
     */
    protected function createAlertsQuery(): Query
    {
        if (! Backend::supportsNotifications() || $this->event->alert_count < 1) {
            $this->httpBadRequest($this->translate('This event has no alerts'));
        }

        $query = AlertHistory::on($this->getDb())
            ->with(['host', 'service'])
            ->filter(Filter::equal('history_id', $this->event->id));
        $this->applyRestrictions($query);

        return $query;
    }
}
