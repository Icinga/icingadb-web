<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\ProvidedHook\Dashboards;

use Icinga\Module\Dashboards\Hook\DashletControls;
use Icinga\Module\Dashboards\Hook\DashletHook;
use Icinga\Module\Icingadb\Model\HoststateSummary;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use ipl\Orm\Model;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;

/**
 * Offers the tactical overview as a dashlet type for the dashboards module, one donut at a time.
 *
 * `icingadb/tactical` always draws hosts and services together and reads its whole query string as a filter, so
 * it cannot be told which one to draw. The dashlet embeds `TacticalController::hostsAction()` or
 * `servicesAction()` instead, chosen by the Object Type control, and the filter rides along as the query.
 *
 * Like {@see ObjectList}, nothing here queries anything: the route is dispatched in the viewer's own session,
 * so their restrictions apply and the controller's own refresh interval is the dashlet's.
 */
class TacticalOverview extends DashletHook
{
    use FilterColumns;

    /**
     * @var array<string, array{
     *     label: string,
     *     route: string,
     *     model: class-string<Model>,
     *     complete: string
     * }> Every object type, and the route that draws it
     *
     * The first is the default, and where an unknown stored type falls back to.
     */
    private const OBJECT_TYPES = [
        'hosts' => [
            'label' => 'Hosts',
            'route' => 'icingadb/tactical/hosts',
            'model' => HoststateSummary::class,
            'complete' => 'icingadb/hosts/complete',
        ],
        'services' => [
            'label' => 'Services',
            'route' => 'icingadb/tactical/services',
            'model' => ServicestateSummary::class,
            'complete' => 'icingadb/services/complete',
        ],
    ];

    /** @var string The combined view, which draws both and is claimed by {@see self::capture()} too */
    private const COMBINED_ROUTE = 'icingadb/tactical';

    public function getName(): string
    {
        return 'tactical-overview';
    }

    public function getLabel(): string
    {
        return $this->translateInDomain('icingadb', 'Tactical Overview');
    }

    public function getDescription(): string
    {
        return $this->translateInDomain('icingadb', 'The state of hosts or services at a glance.');
    }

    public function getModuleLabel(): ?string
    {
        return 'Icinga DB';
    }

    public function configure(DashletControls $controls): void
    {
        $types = [];

        foreach (self::OBJECT_TYPES as $name => $objectType) {
            $types[$name] = $this->translateInDomain('icingadb', $objectType['label']);
        }

        $controls->objectType($types);
        $controls->filter();
    }

    /**
     * Complete against the list of the same objects, not against the tactical route
     *
     * `icingadb/tactical/complete` suggests from the services summary whatever is being drawn, which would
     * offer service columns to the hosts donut. The summaries are views over the same tables the lists
     * query, so the lists' own columns are the ones that filter means.
     */
    public function getFilterSuggestionUrl(?string $objectType): ?Url
    {
        return isset(self::OBJECT_TYPES[$objectType])
            ? Url::fromPath(self::OBJECT_TYPES[$objectType]['complete'])
            : null;
    }

    private function filterModel(?string $objectType): ?Model
    {
        if (! isset(self::OBJECT_TYPES[$objectType])) {
            return null;
        }

        $class = self::OBJECT_TYPES[$objectType]['model'];

        return new $class();
    }

    public function getRoute(array $settings): ?Url
    {
        $objectType = $settings[DashletControls::OBJECT_TYPE] ?? '';

        if (! isset(self::OBJECT_TYPES[$objectType])) {
            $objectType = array_key_first(self::OBJECT_TYPES);
        }

        return Url::fromPath(self::OBJECT_TYPES[$objectType]['route']);
    }

    /**
     * Claim the tactical overview, combined or single, with its filter left in the URL
     *
     * The combined view has one filter for both donuts, so it becomes one dashlet whose type the author can
     * switch in the panel with the filter kept. It starts as Services when the filter names a `service.`
     * column, because a hosts summary cannot evaluate one; otherwise as Hosts.
     */
    public function capture(Url $url): ?array
    {
        $path = $url->getPath();

        foreach (self::OBJECT_TYPES as $name => $objectType) {
            if ($objectType['route'] === $path) {
                return [DashletControls::OBJECT_TYPE => $name];
            }
        }

        if ($path !== self::COMBINED_ROUTE) {
            return null;
        }

        $filter = QueryString::parse((string) $url->getParams());

        return [DashletControls::OBJECT_TYPE => self::namesServiceColumn($filter) ? 'services' : 'hosts'];
    }

    private static function namesServiceColumn(Filter\Rule $rule): bool
    {
        if ($rule instanceof Filter\Chain) {
            foreach ($rule as $child) {
                if (self::namesServiceColumn($child)) {
                    return true;
                }
            }

            return false;
        }

        return $rule instanceof Filter\Condition && strncmp((string) $rule->getColumn(), 'service.', 8) === 0;
    }
}
