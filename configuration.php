<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb {

    use Icinga\Application\Icinga;
    use Icinga\Authentication\Auth;
    use Icinga\Module\Icingadb\Web\Navigation\Renderer\HostProblemsBadge;
    use Icinga\Module\Icingadb\Web\Navigation\Renderer\ServiceProblemsBadge;
    use Icinga\Util\StringHelper;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;

    /** @var \Icinga\Application\Modules\Module $this */

    $this->provideSetupWizard('Icinga\Module\Icingadb\Setup\IcingaDbWizard');

    $this->providePermission(
        'icingadb/command/*',
        $this->translate('Allow all commands')
    );
    $this->providePermission(
        'icingadb/command/schedule-check',
        $this->translate('Allow to schedule host and service checks')
    );
    $this->providePermission(
        'icingadb/command/schedule-check/active-only',
        $this->translate('Allow to schedule host and service checks (Only on objects with active checks enabled)')
    );
    $this->providePermission(
        'icingadb/command/acknowledge-problem',
        $this->translate('Allow to acknowledge host and service problems')
    );
    $this->providePermission(
        'icingadb/command/remove-acknowledgement',
        $this->translate('Allow to remove problem acknowledgements')
    );
    $this->providePermission(
        'icingadb/command/comment/*',
        $this->translate('Allow to add and delete host and service comments')
    );
    $this->providePermission(
        'icingadb/command/comment/add',
        $this->translate('Allow to add host and service comments')
    );
    $this->providePermission(
        'icingadb/command/comment/delete',
        $this->translate('Allow to delete host and service comments')
    );
    $this->providePermission(
        'icingadb/command/downtime/*',
        $this->translate('Allow to schedule and delete host and service downtimes')
    );
    $this->providePermission(
        'icingadb/command/downtime/schedule',
        $this->translate('Allow to schedule host and service downtimes')
    );
    $this->providePermission(
        'icingadb/command/downtime/delete',
        $this->translate('Allow to delete host and service downtimes')
    );
    $this->providePermission(
        'icingadb/command/process-check-result',
        $this->translate('Allow to process host and service check results')
    );
    $this->providePermission(
        'icingadb/command/feature/instance',
        $this->translate('Allow to toggle instance-wide features')
    );
    $this->providePermission(
        'icingadb/command/feature/object/*',
        $this->translate('Allow to toggle all features on host and service objects')
    );
    $this->providePermission(
        'icingadb/command/feature/object/active-checks',
        $this->translate('Allow to toggle active checks on host and service objects')
    );
    $this->providePermission(
        'icingadb/command/feature/object/passive-checks',
        $this->translate('Allow to toggle passive checks on host and service objects')
    );
    $this->providePermission(
        'icingadb/command/feature/object/notifications',
        $this->translate('Allow to toggle notifications on host and service objects')
    );
    $this->providePermission(
        'icingadb/command/feature/object/event-handler',
        $this->translate('Allow to toggle event handlers on host and service objects')
    );
    $this->providePermission(
        'icingadb/command/feature/object/flap-detection',
        $this->translate('Allow to toggle flap detection on host and service objects')
    );
    $this->providePermission(
        'icingadb/command/send-custom-notification',
        $this->translate('Allow to send custom notifications for hosts and services')
    );

    $this->providePermission(
        'icingadb/object/show-source',
        $this->translate('Allow to view an object\'s source data. (May contain sensitive data!)')
    );

    $this->provideRestriction(
        'icingadb/filter/objects',
        $this->translate('Restrict access to the Icinga objects that match the filter')
    );

    $this->provideRestriction(
        'icingadb/filter/hosts',
        $this->translate('Restrict access to the Icinga hosts and services that match the filter')
    );

    $this->provideRestriction(
        'icingadb/filter/services',
        $this->translate('Restrict access to the Icinga services that match the filter')
    );

    $this->provideRestriction(
        'icingadb/blacklist/routes',
        $this->translate('Prevent access to routes that are part of the list')
    );

    $this->provideRestriction(
        'icingadb/blacklist/variables',
        $this->translate('Hide custom variables of Icinga objects that are part of the list')
    );

    $this->provideRestriction(
        'icingadb/protect/variables',
        $this->translate('Obfuscate custom variable values of Icinga objects that are part of the list')
    );

    if (! $this::exists('monitoring')) {
        /*
        * Available navigation items
        */
        $this->provideNavigationItem('icingadb-host-action', $this->translate('Host Action'));
        $this->provideNavigationItem('icingadb-service-action', $this->translate('Service Action'));

        /**
         * Search urls
         */
        $this->provideSearchUrl(
            $this->translate('Tactical Overview'),
            'icingadb/tactical',
            100
        );
        $this->provideSearchUrl(
            $this->translate('Hosts'),
            'icingadb/hosts?sort=host.state.severity&limit=10',
            99
        );
        $this->provideSearchUrl(
            $this->translate('Services'),
            'icingadb/services?sort=service.state.severity&limit=10',
            98
        );
        $this->provideSearchUrl(
            $this->translate('Hostgroups'),
            'icingadb/hostgroups?limit=10',
            97
        );
        $this->provideSearchUrl(
            $this->translate('Servicegroups'),
            'icingadb/servicegroups?limit=10',
            96
        );

        /**
         * Current Incidents
         */
        $dashboard = $this->dashboard(N_('Current Incidents'), ['priority' => 50]);
        $dashboard->add(
            N_('Service Problems'),
            'icingadb/services?service.state.is_problem=y'
            . '&view=minimal&limit=32&sort=service.state.severity desc',
            100
        );
        $dashboard->add(
            N_('Recently Recovered Services'),
            'icingadb/services?service.state.soft_state=0'
            . '&view=minimal&limit=32&sort=service.state.last_state_change desc',
            110
        );
        $dashboard->add(
            N_('Host Problems'),
            'icingadb/hosts?host.state.is_problem=y'
            . '&view=minimal&limit=32&sort=host.state.severity desc',
            120
        );

        /**
         * Overdue
         */
        $dashboard = $this->dashboard(N_('Overdue'), ['priority' => 70]);
        $dashboard->add(
            N_('Late Host Check Results'),
            'icingadb/hosts?host.state.is_overdue=y'
            . '&view=minimal&limit=15&sort=host.state.severity desc',
            100
        );
        $dashboard->add(
            N_('Late Service Check Results'),
            'icingadb/services?service.state.is_overdue=y'
            . '&view=minimal&limit=15&sort=service.state.severity desc',
            110
        );
        $dashboard->add(
            N_('Acknowledgements Active For At Least Three Days'),
            'icingadb/comments?comment.entry_type=ack&comment.entry_time<-3 days'
            . '&view=minimal&limit=15&sort=comment.entry_time',
            120
        );
        $dashboard->add(
            N_('Downtimes Active For At Least Three Days'),
            'icingadb/downtimes?downtime.is_in_effect=y&downtime.scheduled_start_time<-3 days'
            . '&view=minimal&limit=15&sort=downtime.start_time',
            130
        );

        /**
         * Muted
         */
        $dashboard = $this->dashboard(N_('Muted'), ['priority' => 80]);
        $dashboard->add(
            N_('Disabled Service Notifications'),
            'icingadb/services?service.notifications_enabled=n'
            . '&view=minimal&limit=15&sort=service.state.severity desc',
            100
        );
        $dashboard->add(
            N_('Disabled Host Notifications'),
            'icingadb/hosts?host.notifications_enabled=n'
            . '&view=minimal&limit=15&sort=host.state.severity desc',
            110
        );
        $dashboard->add(
            N_('Disabled Service Checks'),
            'icingadb/services?service.active_checks_enabled=n'
            . '&view=minimal&limit=15&sort=service.state.last_state_change',
            120
        );
        $dashboard->add(
            N_('Disabled Host Checks'),
            'icingadb/hosts?host.active_checks_enabled=n'
            . '&view=minimal&limit=15&sort=host.state.last_state_change',
            130
        );
        $dashboard->add(
            N_('Acknowledged Problem Services'),
            'icingadb/services?service.state.is_acknowledged!=n&service.state.is_problem=y'
            . '&view=minimal&limit=15&sort=service.state.severity desc',
            140
        );
        $dashboard->add(
            N_('Acknowledged Problem Hosts'),
            'icingadb/hosts?host.state.is_acknowledged!=n&host.state.is_problem=y'
            . '&view=minimal&limit=15&sort=host.state.severity desc',
            150
        );

        /**
         * @var \Icinga\Application\Modules\Module $this
         *
         * Problems section in case monitoring is disabled
         */
        $problemSection = $this->menuSection(N_('Problems'), [
            'renderer' => array(
                'TotalProblemsBadge',
                'state' => 'critical'
            ),
            'icon'     => 'attention-circled',
            'priority' => 20
        ]);
        $problemSection->add(N_('Host Problems'), [
            'renderer'    => (new HostProblemsBadge())->disableLink(),
            'icon'        => 'server',
            'description' => $this->translate('List current host problems'),
            'url'         => 'icingadb/hosts?host.state.is_problem=y'
                . '&sort=host.state.severity desc',
            'priority'    => 50
        ]);
        $problemSection->add(N_('Service Problems'), [
            'renderer'    => (new ServiceProblemsBadge())->disableLink(),
            'icon'        => 'cog',
            'description' => $this->translate('List current service problems'),
            'url'         => 'icingadb/services?service.state.is_problem=y'
                . '&sort=service.state.severity desc',
            'priority'    => 60
        ]);
        $problemSection->add(N_('Service Grid'), [
            'icon'        => 'cogs',
            'description' => $this->translate('Display service problems as grid'),
            'url'         => 'icingadb/services/grid?problems',
            'priority'    => 70
        ]);

        $problemSection->add(N_('Current Downtimes'), [
            'description' => $this->translate('List current downtimes'),
            'url'         => 'icingadb/downtimes?downtime.is_in_effect=y',
            'priority'    => 80,
            'icon'        => 'plug'
        ]);

        /**
         * @var \Icinga\Application\Modules\Module $this
         *
         * Overview section in case monitoring is disabled
         */
        $overviewSection = $this->menuSection('Overview', [
            'icon'     => 'binoculars',
            'priority' => 30
        ]);

        $overviewSection->add(N_('Tactical Overview'), [
            'url'         => 'icingadb/tactical',
            'description' => $this->translate('Open tactical overview'),
            'priority'    => 40,
            'icon'        => 'chart-pie'
        ]);
        $overviewSection->add(N_('Hosts'), [
            'priority'    => 50,
            'description' => $this->translate('List hosts'),
            'url'         => 'icingadb/hosts',
            'icon'        => 'server'
        ]);
        $overviewSection->add(N_('Services'), [
            'priority'    => 50,
            'description' => $this->translate('List services'),
            'url'         => 'icingadb/services',
            'icon'        => 'cog'
        ]);
        $auth = Auth::getInstance();
        $routeBlacklist = [];
        if ($auth->isAuthenticated() && ! $auth->getUser()->isUnrestricted()) {
            // The empty array is for PHP pre 7.4, older versions require at least a single param for array_merge
            $routeBlacklist = array_flip(array_merge([], ...array_map(function ($restriction) {
                return StringHelper::trimSplit($restriction);
            }, $auth->getRestrictions('icingadb/blacklist/routes'))));
        }

        if (! array_key_exists('servicegroups', $routeBlacklist)) {
            $overviewSection->add(N_('Service Groups'), [
                'description' => $this->translate('List service groups'),
                'url'         => 'icingadb/servicegroups',
                'priority'    => 60,
                'icon'        => 'cogs'
            ]);
        }

        if (! array_key_exists('hostgroups', $routeBlacklist)) {
            $overviewSection->add(N_('Host Groups'), [
                'description' => $this->translate('List host groups'),
                'url'         => 'icingadb/hostgroups',
                'priority'    => 60,
                'icon'        => 'network-wired'
            ]);
        }

        if (! array_key_exists('users', $routeBlacklist)) {
            $overviewSection->add(N_('Users'), [
                'description' => $this->translate('List users'),
                'url'         => 'icingadb/users',
                'priority'    => 70,
                'icon'        => 'user-friends'
            ]);
        }

        if (! array_key_exists('usergroups', $routeBlacklist)) {
            $overviewSection->add(N_('User Groups'), [
                'description' => $this->translate('List user groups'),
                'url'         => 'icingadb/usergroups',
                'priority'    => 70,
                'icon'        => 'users'
            ]);
        }

        $overviewSection->add(N_('Comments'), [
            'url'         => 'icingadb/comments',
            'description' => $this->translate('List comments'),
            'priority'    => 80,
            'icon'        => 'comments'
        ]);
        $overviewSection->add(N_('Downtimes'), [
            'url'         => 'icingadb/downtimes',
            'description' => $this->translate('List downtimes'),
            'priority'    => 80,
            'icon'        => 'plug'
        ]);

        /**
         * @var \Icinga\Application\Modules\Module $this
         *
         * History section in case monitoring is disabled
         */

        $section = $this->menuSection(N_('History'), array(
            'icon'     => 'history',
            'priority' => 90
        ));

        $section->add(N_('Event Overview'), array(
            'icon'        => 'history',
            'description' => $this->translate('Open event overview'),
            'priority'    => 20,
            'url'         => 'icingadb/history'
        ));
        $section->add(N_('Notifications'), array(
            'icon'        => 'bell',
            'description' => $this->translate('List notifications'),
            'priority'    => 30,
            'url'         => 'icingadb/notifications',
        ));
    } else {
        /*
        * Available navigation items
        */
        $this->provideNavigationItem(
            'icingadb-host-action',
            $this->translate('Host Action') . ' (Icinga DB)'
        );
        $this->provideNavigationItem(
            'icingadb-service-action',
            $this->translate('Service Action') . ' (Icinga DB)'
        );

        /** @var \Icinga\Application\Modules\Module $this */
        $section = $this->menuSection('Icinga DB', [
            'icon'     => 'database',
            'priority' => 30
        ]);

        $section->add(N_('Hosts'), [
            'priority'      => 10,
            'description'   => $this->translate('List hosts'),
            'renderer'      => 'HostProblemsBadge',
            'url'           => 'icingadb/hosts',
            'icon'          => 'server'
        ]);
        $section->add(N_('Services'), [
            'priority'      => 20,
            'description'   => $this->translate('List services'),
            'renderer'      => 'ServiceProblemsBadge',
            'url'           => 'icingadb/services',
            'icon'          => 'cog'
        ]);
        $section->add(N_('Downtimes'), [
            'url'           => 'icingadb/downtimes',
            'priority'      => 30,
            'description'   => $this->translate('List downtimes'),
            'icon'          => 'plug'
        ]);
        $section->add(N_('Comments'), [
            'url'           => 'icingadb/comments',
            'priority'      => 40,
            'description'   => $this->translate('List comments'),
            'icon'          => 'comments'
        ]);
        $section->add(N_('Notifications'), [
            'url'           => 'icingadb/notifications',
            'priority'      => 50,
            'description'   => $this->translate('List notifications'),
            'icon'          => 'bell'
        ]);
        $section->add(N_('Service Grid'), [
            'icon'        => 'cog',
            'description' => $this->translate('Display service problems as grid'),
            'url'         => 'icingadb/services/grid?problems',
            'priority'    => 70
        ]);

        $auth = Auth::getInstance();
        $routeBlacklist = [];
        if ($auth->isAuthenticated() && ! $auth->getUser()->isUnrestricted()) {
            // The empty array is for PHP pre 7.4, older versions require at least a single param for array_merge
            $routeBlacklist = array_flip(array_merge([], ...array_map(function ($restriction) {
                return StringHelper::trimSplit($restriction);
            }, $auth->getRestrictions('icingadb/blacklist/routes'))));
        }

        if (! array_key_exists('users', $routeBlacklist)) {
            $section->add(N_('Users'), [
                'url'           => 'icingadb/users',
                'priority'      => 60,
                'description'   => $this->translate('List users'),
                'icon'          => 'user-friends'
            ]);
        }

        if (! array_key_exists('usergroups', $routeBlacklist)) {
            $section->add(N_('User Groups'), [
                'url'           => 'icingadb/usergroups',
                'priority'      => 70,
                'description'   => $this->translate('List user groups'),
                'icon'          => 'users'
            ]);
        }

        if (! array_key_exists('hostgroups', $routeBlacklist)) {
            $section->add(N_('Host Groups'), [
                'url'           => 'icingadb/hostgroups',
                'priority'      => 80,
                'description'   => $this->translate('List host groups'),
                'icon'          => 'network-wired'
            ]);
        }

        if (! array_key_exists('servicegroups', $routeBlacklist)) {
            $section->add(N_('Service Groups'), [
                'url'           => 'icingadb/servicegroups',
                'priority'      => 80,
                'description'   => $this->translate('List service groups'),
                'icon'          => 'cogs'
            ]);
        }

        $section->add(N_('History'), [
            'url'           => 'icingadb/history',
            'priority'      => 90,
            'description'   => $this->translate('List history'),
            'icon'          => 'history'
        ]);
        $section->add(N_('Health'), [
            'url'           => 'icingadb/health',
            'priority'      => 100,
            'description'   => $this->translate('Open health overview'),
            'icon'          => 'heartbeat'
        ]);
        $section->add(N_('Tactical Overview'), [
            'url'           => 'icingadb/tactical',
            'priority'      => 110,
            'description'   => $this->translate('Open tactical overview'),
            'icon'          => 'chart-pie'
        ]);
    }

    $this->provideConfigTab('database', [
        'label' => t('Database'),
        'title' => t('Configure the database backend'),
        'url'   => 'config/database'
    ]);
    $this->provideConfigTab('redis', [
        'label' => t('Redis'),
        'title' => t('Configure the Redis connections'),
        'url'   => 'config/redis'
    ]);
    $this->provideConfigTab('command-transports', [
        'label' => t('Command Transports'),
        'title' => t('Configure command transports'),
        'url'   => 'command-transport'
    ]);

    $cssDirectory = $this->getCssDir();
    $cssFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $cssDirectory,
        RecursiveDirectoryIterator::CURRENT_AS_PATHNAME | RecursiveDirectoryIterator::SKIP_DOTS
    ));
    foreach ($cssFiles as $path) {
        $this->provideCssFile(ltrim(substr($path, strlen($cssDirectory)), DIRECTORY_SEPARATOR));
    }

    $this->provideJsFile('action-list.js');
    $this->provideJsFile('loadmore.js');

    $forIe11 = false;
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $forIe11 = (bool) preg_match('/Trident\/7.0;.*rv:11/', $_SERVER['HTTP_USER_AGENT']);
    }

    $mg = Icinga::app()->getModuleManager();
    if ($mg->hasEnabled('monitoring') && ! $forIe11) {
        $this->provideJsFile('migrate.js');
    }
}
