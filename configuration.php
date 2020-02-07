<?php

namespace Icinga\Module\Icingadb
{
    use Icinga\Authentication\Auth;

    /** @var \Icinga\Application\Modules\Module $this */
    $section = $this->menuSection(N_('Icinga DB'), [
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

    $this->provideConfigTab('database', [
        'label' => $this->translate('Database'),
        'title' => $this->translate('Configure the database backend'),
        'url'   => 'config/database'
    ]);
    $this->provideConfigTab('redis', [
        'label' => $this->translate('Redis'),
        'title' => $this->translate('Configure the Redis connections'),
        'url'   => 'config/redis'
    ]);
    $this->provideConfigTab('command-transports', [
        'label' => $this->translate('Command Transports'),
        'title' => $this->translate('Configure command transports'),
        'url'   => 'config/command-transports'
    ]);
    $this->provideConfigTab('security', [
        'label' => $this->translate('Security'),
        'title' => $this->translate('Configure security related settings'),
        'url'   => 'config/security'
    ]);

    $this->requireCssFile('balls.less', 'ipl');

    $this->provideCssFile('lists.less');
    $this->provideCssFile('mixins.less');
    $this->provideCssFile('widgets.less');
    $this->provideCssFile('icinga-icons.less');

    $this->provideJsFile('action-list.js');
    $this->provideJsFile('migrate.js');
    $this->provideJsFile('loadmore.js');
}
