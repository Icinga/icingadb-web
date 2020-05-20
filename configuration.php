<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb
{
    use Icinga\Application\Logger;
    use Icinga\Authentication\Auth;

    /** @var \Icinga\Application\Modules\Module $this */

    $this->provideSetupWizard('Icinga\Module\Icingadb\Setup\IcingaDbWizard');

    if (! $this::exists('ipl')) {
        // TODO: Replace this once we have proper dependency management
        Logger::warning(t('Module "ipl" is not enabled. This module is mandatory for Icinga DB Web'));
        return;
    }

    if (! $this::exists('monitoring')) {
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
    }

    /** @var \Icinga\Application\Modules\Module $this */
    $section = $this->menuSection('Icinga DB', [
        'icon'     => 'database',
        'priority' => 30
    ]);

    $section->add(N_('Hosts'), [
        'priority' => 10,
        'renderer' => 'HostProblemsBadge',
        'url'      => 'icingadb/hosts'
    ]);
    $section->add(N_('Services'), [
        'priority' => 20,
        'renderer' => 'ServiceProblemsBadge',
        'url'      => 'icingadb/services'
    ]);
    $section->add(N_('Downtimes'), [
        'url' => 'icingadb/downtimes',
        'priority' => 30
    ]);
    $section->add(N_('Comments'), [
        'url' => 'icingadb/comments',
        'priority' => 40
    ]);
    $section->add(N_('Notifications'), [
        'url' => 'icingadb/notifications',
        'priority' => 50
    ]);

    $auth = Auth::getInstance();
    if ($auth->hasPermission('*') || ! $auth->hasPermission('no-monitoring/contacts')) {
        $section->add(N_('Users'), [
            'url' => 'icingadb/users',
            'priority' => 60
        ]);
        $section->add(N_('User Groups'), [
            'url' => 'icingadb/usergroups',
            'priority' => 70
        ]);
    }

    $section->add(N_('Host Groups'), [
        'url' => 'icingadb/hostgroups',
        'priority' => 80
    ]);
    $section->add(N_('Service Groups'), [
        'url' => 'icingadb/servicegroups',
        'priority' => 80
    ]);
    $section->add(N_('History'), [
        'url' => 'icingadb/history',
        'priority' => 90
    ]);
    $section->add(N_('Health'), [
        'url' => 'icingadb/health',
        'priority' => 100
    ]);
    $section->add(N_('Tactical Overview'), [
        'url' => 'icingadb/tactical',
        'priority' => 110
    ]);

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
        'url'   => 'config/command-transports'
    ]);
    $this->provideConfigTab('security', [
        'label' => t('Security'),
        'title' => t('Configure security related settings'),
        'url'   => 'config/security'
    ]);

    $this->requireCssFile('balls.less', 'ipl');

    $this->provideCssFile('common.less');
    $this->provideCssFile('lists.less');
    $this->provideCssFile('mixins.less');
    $this->provideCssFile('widgets.less');
    $this->provideCssFile('icinga-icons.less');

    $this->provideJsFile('action-list.js');
    $this->provideJsFile('migrate.js');
    $this->provideJsFile('loadmore.js');
}
