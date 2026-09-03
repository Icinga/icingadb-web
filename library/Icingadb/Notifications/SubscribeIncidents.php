<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Notifications;

use ArrayIterator;
use CallbackFilterIterator;
use Icinga\Module\Icingadb\Common\Backend;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Util\Environment;
use ipl\Html\Form;
use ipl\Orm\Query;
use ipl\Web\Url;
use IteratorIterator;

/**
 * @phpstan-require-extends Controller
 */
trait SubscribeIncidents
{
    /**
     * Subscribe or unsubscribe the incident of each object
     *
     * @param bool $subscribe
     * @param array<Host|Service>|Query<Host|Service> $objects
     * @param Url $redirectUrl The URL to redirect to after the form has been handled
     *
     * @return void
     */
    protected function handleSubscription(bool $subscribe, array|Query $objects, Url $redirectUrl): void
    {
        if (Backend::notificationsSetUp()) {
            $form = (new SubscriptionForm($subscribe))
                ->setObjectCount(count($objects))
                ->on(Form::ON_SUBMIT, function () use ($subscribe, $objects, $redirectUrl) {
                    $username = $this->getAuth()->getUser()->getUsername();
                    $granted = new CallbackFilterIterator(
                        is_array($objects) ? new ArrayIterator($objects) : new IteratorIterator($objects),
                        fn($object) => $this->isGrantedOn('icingadb/notifications/subscribe', $object)
                    );
                    $granted->rewind();
                    if ($granted->valid()) {
                        foreach (IncidentFinder::forObjects($granted) as $incident) {
                            if ($subscribe) {
                                $incident->addSubscriber($username);
                            } else {
                                $incident->removeSubscriber($username);
                            }
                        }
                    }

                    $this->redirectNow($redirectUrl);
                });

            $form->handleRequest($this->getServerRequest());

            $this->addContent($form);
        }
    }

    protected function handleBulkSubscription(bool $subscribe): void
    {
        $this->assertIsGrantedOnCommandTargets('icingadb/notifications/subscribe');

        Environment::raiseMemoryLimit();
        Environment::raiseExecutionTime();

        $this->handleSubscription($subscribe, $this->getCommandTargets(), $this->getCommandTargetsUrl());
    }

    public function subscribeAction(): void
    {
        $this->handleBulkSubscription(true);
    }

    public function unsubscribeAction(): void
    {
        $this->handleBulkSubscription(false);
    }
}
