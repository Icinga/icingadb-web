<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Notifications;

use ArrayIterator;
use CallbackFilterIterator;
use DateTime;
use Icinga\Module\Icingadb\Common\Backend;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use InvalidArgumentException;
use IteratorIterator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @phpstan-require-extends CommandForm
 */
trait ManagesIncidents
{
    /**
     * Add or remove the current user as manager of all incidents related to the form's objects
     *
     * @param bool $manage
     * @param ?DateTime $ackSetTime Necessary if `$manage` is `true`, the same value sent as `set_time`
     *
     * @return void
     *
     * @throws InvalidArgumentException If `$manage` is `true` but no `$ackSetTime` is given for icinga2 >= v2.16.6
     */
    protected function manageIncidents(bool $manage, ?DateTime $ackSetTime = null): void
    {
        if ($this->getAuth()->hasPermission('icingadb/notifications/manage') && Backend::notificationsSetUp()) {
            if (
                $manage
                && $ackSetTime === null
                && version_compare(Backend::getIcinga2Version(), 'v2.16.6', '>=')
            ) {
                throw new InvalidArgumentException('An acknowledgement set time is required to manage incidents');
            }

            $username = $this->getAuth()->getUser()->getUsername();
            $objects = $this->getObjects();
            $objects = new CallbackFilterIterator(
                is_array($objects) ? new ArrayIterator($objects) : new IteratorIterator($objects),
                fn ($object) => $this->isGrantedOn('icingadb/notifications/manage', $object)
            );

            $objects->rewind();
            if ($objects->valid()) {
                $ackTimes = [];
                if (! $manage) {
                    foreach ($objects as $object) {
                        $ackTimes[$this->objectKey($object)] = $object->state->acknowledgement_set_time;
                    }
                }

                foreach (IncidentFinder::forObjects($objects) as $incident) {
                    if ($manage) {
                        $incident->addManager(
                            $username,
                            ['downtime_start', 'downtime_end', 'flapping_start', 'flapping_end'],
                            $ackSetTime !== null
                                ? $this->deriveDeterministicEventUUID(
                                    'ack_set',
                                    $incident->getObjectIdTags(),
                                    (int) $ackSetTime->format('U') * 1000
                                )
                                : null
                        );
                    } else {
                        $tags = $incident->getObjectIdTags();
                        $ackTime = $ackTimes[$this->objectKey($tags)] ?? null;
                        $incident->removeManager(
                            $ackTime !== null
                                ? $this->deriveDeterministicEventUUID(
                                    'ack_clear',
                                    $tags,
                                    (int) $ackTime->format('Uv')
                                )
                                : null
                        );
                    }
                }
            }
        }
    }

    private function objectKey(Host|Service|array $objectOrTags): string
    {
        if (is_array($objectOrTags)) {
            $environment = $objectOrTags['environment'];
            $name = isset($objectOrTags['service'])
                ? $objectOrTags['host'] . '!' . $objectOrTags['service']
                : $objectOrTags['host'];
        } elseif ($objectOrTags instanceof Service) {
            $environment = bin2hex($objectOrTags->environment_id);
            $name = $objectOrTags->host->name . '!' . $objectOrTags->name;
        } else {
            $environment = bin2hex($objectOrTags->environment_id);
            $name = $objectOrTags->name;
        }

        return $environment . '!' . $name;
    }

    /**
     * Mirror the way icingadb's DeriveDeterministicEventUUID() uses objectpacker.go to generate its UUIDs
     *
     * @param string $eventType
     * @param array<string, string> $tags
     * @param int $time
     *
     * @return string
     */
    private function pack(string $eventType, array $tags, int $time): string
    {
        $packString = fn (string $str) => chr(4) . pack('J', strlen($str)) . $str;

        $name = isset($tags['service']) ? $tags['host'] . '!' . $tags['service'] : $tags['host'];

        // The 4 arguments are passed to objectpacker.go::packValue() in a slice of type any, which writes these
        // initial bytes
        $data = chr(5) . pack('J', 4);

        $data .= $packString($tags['environment']);
        $data .= $packString($eventType);
        $data .= $packString($name);
        $data .= chr(3) . pack('E', (float) $time);

        return $data;
    }

    /**
     * Derive a deterministic UUID from the given information
     *
     * Essentially a reimplementation of icingadb/pkg/notifications/utils.go::DeriveDeterministicEventUUID()
     *
     * @param string $eventType
     * @param array<string, string> $tags
     * @param int $time
     *
     * @return UuidInterface
     */
    private function deriveDeterministicEventUUID(string $eventType, array $tags, int $time): UuidInterface
    {
        return Uuid::uuid5(Uuid::NIL, hash('sha1', $this->pack($eventType, $tags, $time), true));
    }
}
