<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Notifications;

use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Notifications\Integrations\Exception\IncidentNotFoundException;
use Icinga\Module\Notifications\Integrations\Incident;
use Icinga\Module\Notifications\Integrations\Incidents;
use InvalidArgumentException;
use ipl\Stdlib\Seq;

class IncidentFinder
{
    /**
     * Find the open incident related to the given object
     *
     * @param Host|Service $object
     *
     * @return Incident
     *
     * @throws IncidentNotFoundException If no incident for the object is found, thrown once any function
     *                                   on the returned instance is called
     */
    public static function forObject(Host|Service $object): Incident
    {
        return Incidents::get(self::buildTags($object));
    }

    /**
     * Get the matching incident for each of the given objects
     *
     * @param iterable<Host|Service> $objects
     *
     * @return Incidents
     *
     * @throws InvalidArgumentException If $objects is empty
     */
    public static function forObjects(iterable $objects): Incidents
    {
        return Incidents::getAll(Seq::map($objects, fn(Host|Service $object) => self::buildTags($object)));
    }

    /**
     * Build the tags to match the given host/service
     *
     * @param Host|Service $object
     *
     * @return array<string, string>
     */
    private static function buildTags(Host|Service $object): array
    {
        if ($object instanceof Host) {
            return [
                'host' => $object->name,
                'environment' => bin2hex($object->environment_id)
            ];
        }

        return [
            'host' => $object->host->name,
            'service' => $object->name,
            'environment' => bin2hex($object->environment_id)
        ];
    }
}
