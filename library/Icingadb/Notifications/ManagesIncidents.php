<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Notifications;

use ArrayIterator;
use CallbackFilterIterator;
use Icinga\Module\Icingadb\Common\Backend;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use IteratorIterator;

/**
 * @phpstan-require-extends CommandForm
 */
trait ManagesIncidents
{
    /**
     * Add or remove the current user as manager of all incidents related to the form's objects
     *
     * @param bool $manage
     *
     * @return void
     */
    protected function manageIncidents(bool $manage): void
    {
        if ($this->getAuth()->hasPermission('icingadb/notifications/manage') && Backend::notificationsSetUp()) {
            $username = $this->getAuth()->getUser()->getUsername();
            $objects = $this->getObjects();
            $objects = new CallbackFilterIterator(
                is_array($objects) ? new ArrayIterator($objects) : new IteratorIterator($objects),
                fn ($object) => $this->isGrantedOn('icingadb/notifications/manage', $object)
            );

            $objects->rewind();
            if ($objects->valid()) {
                foreach (IncidentFinder::forObjects($objects) as $incident) {
                    if ($manage) {
                        $incident->addManager($username);
                    } else {
                        $incident->removeManager($username);
                    }
                }
            }
        }
    }
}
