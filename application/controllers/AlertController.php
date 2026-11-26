<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Application\Icinga;
use Icinga\Module\Icingadb\Model\AlertHistory;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\AlertDetail;
use Icinga\Module\Icingadb\Widget\Detail\ObjectHeader;
use Icinga\Module\Notifications\Common\Database;
use Icinga\Module\Notifications\Model\Channel;
use Icinga\Module\Notifications\Model\Contact;
use Icinga\Module\Notifications\Model\Contactgroup;
use Icinga\Module\Notifications\Model\Schedule;
use ipl\Stdlib\Filter;
use ipl\Web\Url;

class AlertController extends Controller
{
    protected AlertHistory $alert;

    public function init()
    {
        $this->addTitleTab($this->translate('Alert'));

        $this->assertPermission('icingadb/notifications/usage');

        $id = hex2bin($this->params->getRequired('id'));

        $query = AlertHistory::on($this->getDb())
            ->with(['host', 'host.state', 'service', 'service.state'])
            ->filter(Filter::equal('id', $id))
            ->disableDefaultSort()
            ->limit(1);

        $this->applyRestrictions($query);

        /** @var ?AlertHistory $alert */
        $alert = $query->first();
        if ($alert === null) {
            $this->httpNotFound($this->translate('Alert not found'));
        }

        $this->alert = $alert;
    }

    public function indexAction()
    {
        $channelUrl = null;
        $contactUrl = null;
        $contactgroupUrl = null;
        $scheduleUrl = null;
        if (Icinga::app()->getModuleManager()->hasEnabled('notifications')) {
            if (isset($this->alert->channel_name) && $this->hasPermission('config/modules')) {
                $channels = iterator_to_array(Channel::on(Database::get())
                    ->columns('id')
                    ->filter(Filter::equal('name', $this->alert->channel_name))
                    ->disableDefaultSort()
                    ->limit(2));
                if (count($channels) === 1) {
                    $channelUrl = Url::fromPath('notifications/channel', ['id' => reset($channels)->id]);
                } elseif (count($channels) > 1) {
                    $channelUrl = Url::fromPath('notifications/channels')
                        ->setFilter(Filter::equal('name', $this->alert->channel_name));
                }
            }

            if (
                isset($this->alert->contact_name)
                && $this->hasPermission('notifications/config/contacts')
            ) {
                $contacts = iterator_to_array(Contact::on(Database::get())
                    ->columns('id')
                    ->filter(Filter::equal('full_name', $this->alert->contact_name))
                    ->disableDefaultSort()
                    ->limit(2));
                if (count($contacts) === 1) {
                    $contactUrl = Url::fromPath('notifications/contact', ['id' => reset($contacts)->id]);
                } elseif (count($contacts) > 1) {
                    $contactUrl = Url::fromPath('notifications/contacts')
                        ->setFilter(Filter::equal('full_name', $this->alert->contact_name));
                }
            }

            if (
                isset($this->alert->contactgroup_name)
                && $this->hasPermission('notifications/config/contacts')
            ) {
                $groups = iterator_to_array(Contactgroup::on(Database::get())
                    ->columns('id')
                    ->filter(Filter::equal('name', $this->alert->contactgroup_name))
                    ->disableDefaultSort()
                    ->limit(2));
                if (count($groups) === 1) {
                    $contactgroupUrl = Url::fromPath('notifications/contact-group', ['id' => reset($groups)->id]);
                } elseif (count($groups) > 1) {
                    $contactgroupUrl = Url::fromPath('notifications/contact-groups')
                        ->setFilter(Filter::equal('name', $this->alert->contactgroup_name));
                }
            } elseif (
                ! isset($this->alert->contactgroup_name)
                && isset($this->alert->schedule_name)
                && $this->hasPermission('notifications/config/schedules')
            ) {
                $schedules = iterator_to_array(Schedule::on(Database::get())
                    ->columns('id')
                    ->filter(Filter::equal('name', $this->alert->schedule_name))
                    ->disableDefaultSort()
                    ->limit(2));
                if (count($schedules) === 1) {
                    $scheduleUrl = Url::fromPath('notifications/schedule', ['id' => reset($schedules)->id]);
                } elseif (count($schedules) > 1) {
                    $scheduleUrl = Url::fromPath('notifications/schedules')
                        ->setFilter(Filter::equal('name', $this->alert->schedule_name));
                }
            }
        }

        $this->addControl(new ObjectHeader($this->alert));
        $this->addContent(new AlertDetail(
            $this->alert,
            $channelUrl,
            $contactUrl,
            $contactgroupUrl,
            $scheduleUrl
        ));
    }
}
