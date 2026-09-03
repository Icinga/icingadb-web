<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\ProvidedHook\Dashboards;

use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Module\Dashboards\Hook\DashletControls;
use Icinga\Module\Dashboards\Hook\DashletHook;
use Icinga\Module\Icingadb\Web\Control\TimestampToggle;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use Icinga\Util\Json;
use ipl\I18n\Translation;
use ipl\Web\Control\LimitControl;
use ipl\Web\Url;

/**
 * Offers this module's object lists as one dashlet type for the dashboards module.
 *
 * One type, not one per list: which objects to list is a control of the dashlet ({@see self::OBJECT_TYPES}), so
 * a seventh list is a row rather than a class. The route, the sort columns and the default order are all data
 * for the same reason.
 *
 * Nothing here queries anything. The dashlet embeds one of this module's own list routes, which the dashboards
 * module renders in the *viewer's* session — so `icingadb/filter/objects` and every other restriction applies to
 * whoever is looking rather than to whoever built the dashboard, and the list refreshes on the interval its own
 * controller asks for.
 *
 * The sort tables are copied from the controllers that own them ({@see
 * \Icinga\Module\Icingadb\Controllers\HostsController::indexAction()} and its five counterparts) rather than
 * read out of them: a controller builds its `SortControl` from a request, and there is none here. The copy is
 * why a column added there has to be added here too — the two cannot drift silently in the other direction,
 * since a stored order this table no longer offers falls back to the default.
 *
 * **Two things about that copy are load-bearing.** A spec is written the way `SortUtil::normalizeSortSpec()`
 * leaves it — comma-separated with no space, since it trims each term (`ipl-orm`
 * `src/Common/SortUtil.php`) — because `SortControl` looks its options up by exactly that string, and a spec
 * spaced the way the controllers write it would not be found. And every `default` below is the order the list
 * itself opens in: four of the six controllers pass no default to `createSortControl()` at all, so theirs comes
 * from the model's own `getDefaultSort()`, which is what is repeated here.
 *
 * **View modes are per object type as well.** Hosts and Services have a fourth, `tabular`
 * ({@see \Icinga\Module\Icingadb\Web\Control\TabularViewModeSwitcher}); the other four lists do not, so they
 * declare no `view` and take the three every list has. The panel renders only the modes the chosen object type
 * has — a mode a list simply does not have is absent rather than greyed out.
 *
 * Two things a tabular dashlet does that a full-page tabular list does not, both for want of a `columns`
 * parameter the dashlet has nowhere to store: it shows only the two columns the controllers pass
 * `createColumnControl()` as defaults, and it renders a column-chooser opener whose choice cannot stick
 * ({@see \Icinga\Module\Icingadb\Widget\ItemTable\StateItemTable}, which adds it whenever columns and a chooser
 * url are both set, with no `showCompact` guard). A Columns control would answer both and does not exist yet.
 */
class ObjectList extends DashletHook
{
    use Translation;

    /**
     * @var array<string, string> The view modes a state list offers, `TabularViewModeSwitcher::$viewModes`
     *
     * Labels, where the switcher's own array holds icon names. Declared here rather than taken from the
     * dashboards module's three because `view` is this module's parameter and `tabular` is its mode.
     */
    private const STATE_VIEW_MODES = [
        'minimal'  => 'Minimal',
        'common'   => 'Default',
        'detailed' => 'Detailed',
        'tabular'  => 'Tabular',
    ];

    /**
     * Every list this module offers as a dashlet
     *
     * `route` is the path rather than a `Links` call, so that a seventh list is one more row and not one more
     * branch — and because two of these six have no factory there to call.
     *
     * `label` is the tab title its own controller sets, so a dashlet names a list the way the menu does.
     *
     * `view` is omitted by the four lists that have only the usual three modes — the dashboards module fills
     * those in, so only a difference is declared.
     *
     * @var array<string, array{
     *     label: string,
     *     route: string,
     *     sort: array<string, string>,
     *     default: string,
     *     view?: array<string, string>
     * }>
     */
    private const OBJECT_TYPES = [
        'hosts' => [
            'label'   => 'Hosts',
            'route'   => 'icingadb/hosts',
            'sort'    => [
                'host.display_name'                                          => 'Name',
                'host.state.severity desc,host.state.last_state_change desc' => 'Severity',
                'host.state.soft_state'                                      => 'Current State',
                'host.state.last_state_change desc'                          => 'Last State Change',
            ],
            'default' => 'host.state.severity desc,host.state.last_state_change desc',
            'view'    => self::STATE_VIEW_MODES,
        ],
        'services' => [
            'label'   => 'Services',
            'route'   => 'icingadb/services',
            'sort'    => [
                'service.display_name'                                             => 'Name',
                'service.state.severity desc,service.state.last_state_change desc' => 'Severity',
                'service.state.soft_state'                                         => 'Current State',
                'service.state.last_state_change desc'                             => 'Last State Change',
                'host.display_name'                                                => 'Host',
            ],
            'default' => 'service.state.severity desc,service.state.last_state_change desc',
            'view'    => self::STATE_VIEW_MODES,
        ],
        'downtimes' => [
            'label'   => 'Downtimes',
            'route'   => 'icingadb/downtimes',
            'sort'    => [
                'downtime.is_in_effect desc,downtime.start_time desc' => 'Is In Effect',
                'downtime.entry_time'                                 => 'Entry Time',
                'host.display_name'                                   => 'Host',
                'service.display_name'                                => 'Service',
                'downtime.author'                                     => 'Author',
                'downtime.start_time desc'                            => 'Start Time',
                'downtime.end_time desc'                              => 'End Time',
                'downtime.scheduled_start_time desc'                  => 'Scheduled Start Time',
                'downtime.scheduled_end_time desc'                    => 'Scheduled End Time',
                'downtime.duration desc'                              => 'Duration',
            ],
            'default' => 'downtime.is_in_effect desc,downtime.start_time desc',
        ],
        'comments' => [
            'label'   => 'Comments',
            'route'   => 'icingadb/comments',
            'sort'    => [
                'comment.entry_time desc'  => 'Entry Time',
                'host.display_name'        => 'Host',
                'service.display_name'     => 'Service',
                'comment.author'           => 'Author',
                'comment.expire_time desc' => 'Expire Time',
            ],
            'default' => 'comment.entry_time desc',
        ],
        // One order each, so their Sorting select has a single option. Left in rather than hidden per object
        // type: the direction beside it still means something, and a control that comes and went with the
        // object type would move every field below it.
        'notifications' => [
            'label'   => 'Notifications',
            'route'   => 'icingadb/notifications',
            'sort'    => ['notification_history.send_time desc' => 'Send Time'],
            'default' => 'notification_history.send_time desc',
        ],
        'history' => [
            'label'   => 'History',
            'route'   => 'icingadb/history',
            'sort'    => ['history.event_time desc,history.event_type desc' => 'Event Time'],
            'default' => 'history.event_time desc,history.event_type desc',
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

        return Url::fromPath(self::OBJECT_TYPES[$objectType]['route']);
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
     * Claim a captured Icinga DB list as an Object List dashlet
     *
     * The reverse of {@see self::getRoute()}: the object type is the route's own path, looked up in the same
     * table, and it is all this hook has to answer — sorting, the view mode and the filter are read off the
     * URL for it, against the very tables and modes declared in {@see self::configure()}.
     *
     * Three parameters are removed rather than left to become part of the filter: none of them are something a
     * dashlet has anywhere to keep. `columns` is one list column short of what would be needed
     * (`self::OBJECT_TYPES` carries no such table, and the class docblock already names the gap); `before` and
     * `last-entry` pin the two time-scrolled lists to the exact moment the page was scrolled to
     * (`refs/icingadb-web application/controllers/HistoryController.php:50-51`,
     * `NotificationsController.php:46-47`), which a captured dashlet showing the same moment forever, rather
     * than "now", would be the wrong answer for.
     */
    public function capture(Url $url): ?array
    {
        $path = $url->getPath();

        foreach (self::OBJECT_TYPES as $name => $objectType) {
            if ($objectType['route'] !== $path) {
                continue;
            }

            $url->remove(['columns', 'before', 'last-entry', TimestampToggle::DEFAULT_TIMESTAMP_MODE_PARAM]);

            return [DashletControls::OBJECT_TYPE => $name];
        }

        return null;
    }

    /**
     * Get the object types as the dashboards module's collector takes them
     *
     * @return array<string, array{
     *     label: string,
     *     sort: array<string, string>,
     *     sortDefault: string,
     *     view?: array<string, string>
     * }>
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
                'label'       => $this->translateInDomain('icingadb', $objectType['label']),
                'sort'        => $sort,
                'sortDefault' => $objectType['default'],
            ];

            // Only where this type differs from the usual three; the dashboards module fills the rest in.
            if (isset($objectType['view'])) {
                $types[$name]['view'] = array_map(
                    function (string $label): string {
                        return $this->translateInDomain('icingadb', $label);
                    },
                    $objectType['view']
                );
            }
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
