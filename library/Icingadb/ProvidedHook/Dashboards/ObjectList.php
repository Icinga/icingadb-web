<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\ProvidedHook\Dashboards;

use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Module\Dashboards\Hook\DashletControls;
use Icinga\Module\Dashboards\Hook\DashletHook;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use Icinga\Util\Json;
use ipl\I18n\Translation;
use ipl\Web\Control\LimitControl;
use ipl\Web\Url;

/**
 * Offers this module's object lists as one dashlet type for the dashboards module.
 *
 * One type, not one per list: which objects to list is a control of the dashlet
 * ({@see self::OBJECT_TYPES}), so adding Downtimes, Comments, Notifications or History is adding a row rather
 * than writing a class. The routes, the sort columns and the default order are all data for the same reason.
 *
 * Nothing here queries anything. The dashlet embeds one of this module's own list routes, which the dashboards
 * module renders in the *viewer's* session — so `icingadb/filter/objects` and every other restriction applies to
 * whoever is looking rather than to whoever built the dashboard, and the list refreshes on the interval its own
 * controller asks for.
 *
 * The sort tables are copied from the controllers that own them
 * ({@see \Icinga\Module\Icingadb\Controllers\HostsController::indexAction()} and its services counterpart)
 * rather than read out of them: a controller builds its `SortControl` from a request, and there is none here.
 * The copy is why a column added there has to be added here too — the two cannot drift silently in the other
 * direction, since a stored order this table no longer offers falls back to the default.
 */
class ObjectList extends DashletHook
{
    use Translation;

    /**
     * @var array<string, array{label: string, route: string, sort: array<string, string>, default: string}>
     *
     * Labels are filled in by {@see self::objectTypes()}, which is where they can be translated.
     */
    private const OBJECT_TYPES = [
        'hosts' => [
            'route'   => 'hosts',
            'sort'    => [
                'host.display_name'                                          => 'Name',
                'host.state.severity desc,host.state.last_state_change desc' => 'Severity',
                'host.state.soft_state'                                      => 'Current State',
                'host.state.last_state_change desc'                          => 'Last State Change',
            ],
            'default' => 'host.state.severity desc,host.state.last_state_change desc',
        ],
        'services' => [
            'route'   => 'services',
            'sort'    => [
                'service.display_name'                                             => 'Name',
                'service.state.severity desc,service.state.last_state_change desc' => 'Severity',
                'service.state.soft_state'                                         => 'Current State',
                'service.state.last_state_change desc'                             => 'Last State Change',
                'host.display_name'                                                => 'Host',
            ],
            'default' => 'service.state.severity desc,service.state.last_state_change desc',
        ],
    ];

    public function getName(): string
    {
        return 'object-list';
    }

    public function getLabel(): string
    {
        // In this module's own domain, explicitly: the string is rendered inside the dashboards module's pages,
        // and this is the catalogue that carries it.
        return $this->translateInDomain('icingadb', 'Object List');
    }

    public function getDescription(): string
    {
        return $this->translateInDomain('icingadb', 'A filtered list of monitored objects.');
    }

    public function configure(DashletControls $controls): void
    {
        $controls->objectType($this->objectTypes());

        $controls->viewMode();
        $controls->limit();

        // No table of its own: each object type declared one above, which is what lets hosts and services
        // offer different orders.
        $controls->sortBy();

        $controls->filter();
    }

    public function getRoute(array $settings): ?Url
    {
        $objectType = $settings[DashletControls::OBJECT_TYPE] ?? '';

        // Defaulted rather than refused: the dashboards module has already normalised this against the table
        // above, so only a type dropped from it since can get here — and a dashlet listing hosts is a better
        // answer than one drawing nothing.
        if (! isset(self::OBJECT_TYPES[$objectType])) {
            $objectType = array_key_first(self::OBJECT_TYPES);
        }

        // Through Links, so the routes stay spelled in the one place this module spells them.
        return self::OBJECT_TYPES[$objectType]['route'] === 'services' ? Links::services() : Links::hosts();
    }

    /**
     * Pin the two controls that would otherwise mean "whatever this reader prefers"
     *
     * A list URL with no `view` and no `limit` does not mean the control's default: both fall back to a
     * *preference of the viewer's*. On a dashboard, which other people read, that is a different list per
     * reader and nothing on screen says so.
     *
     * Pinned to the author's own effective value rather than to the control's default, which is the value they
     * were in fact looking at — the control's default would be right for nobody in particular and would change
     * the dashlet under whoever had chosen otherwise.
     *
     * Reached only when the dashboards module wrote neither, which happens when the author left the control at
     * its declared default and that default is the empty string. Harmless when it did.
     */
    public function normalizeUrl(Url $url): Url
    {
        if (! $url->hasParam(ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM)) {
            $url->setParam(ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM, self::viewModeFor($url->getPath()));
        }

        if (! $url->hasParam(LimitControl::DEFAULT_LIMIT_PARAM)) {
            $url->setParam(LimitControl::DEFAULT_LIMIT_PARAM, self::limit());
        }

        return $url;
    }

    /**
     * Get the object types as the dashboards module's collector takes them
     *
     * @return array<string, array{label: string, sort: array<string, string>, sortDefault: string}>
     */
    private function objectTypes(): array
    {
        $types = [];

        foreach (self::OBJECT_TYPES as $name => $objectType) {
            $sort = [];

            foreach ($objectType['sort'] as $spec => $label) {
                $sort[$spec] = $this->translateInDomain('icingadb', $label);
            }

            $types[$name] = [
                'label'       => $this->translateInDomain('icingadb', ucfirst($name)),
                'sort'        => $sort,
                'sortDefault' => $objectType['default'],
            ];
        }

        return $types;
    }

    /**
     * Get the view mode this user sees a route in when the URL does not say
     *
     * The read half of {@see \Icinga\Module\Icingadb\Web\Controller::createViewModeSwitcher()}, keyed the same
     * way — by route path — because that is the key the switcher's own `ON_SUCCESS` handler writes.
     */
    private static function viewModeFor(string $path): string
    {
        $user = Auth::getInstance()->getUser();

        try {
            $preferredModes = Json::decode($user->getPreferences()->getValue('icingadb', 'view_modes', '[]'), true);
        } catch (JsonDecodeException $e) {
            Logger::error('Failed to load preferred view modes for user "%s": %s', $user->getUsername(), $e);

            $preferredModes = [];
        }

        return $preferredModes[$path] ?? ViewModeSwitcher::DEFAULT_VIEW_MODE;
    }

    /**
     * Get the number of items this user sees when the URL does not say
     *
     * What {@see \Icinga\Web\Controller::getPageSize()} answers and `LimitControl::setDefaultLimit()` is given
     * (`library/Icinga/Web/Controller.php:169-182`, ipl-web `src/Control/LimitControl.php:78-81`).
     */
    private static function limit(): int
    {
        $pageSize = Auth::getInstance()->getUser()->getPreferences()->getValue('icingaweb', 'default_page_size');

        return $pageSize ? (int) $pageSize : LimitControl::DEFAULT_LIMIT;
    }
}
