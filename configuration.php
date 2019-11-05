<?php

namespace Icinga\Module\Icingadb
{
    /** @var \Icinga\Application\Modules\Module $this */
    $section = $this->menuSection(N_('Icinga DB'), [
        'icon'     => 'database',
        'priority' => 30
    ]);

    $section->add(N_('Hosts'), [
        'url' => 'icingadb/hosts',
        'priority' => 10
    ]);
    $section->add(N_('Services'), [
        'url' => 'icingadb/services',
        'priority' => 20
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
    $section->add(N_('Users'), [
        'url' => 'icingadb/users',
        'priority' => 60
    ]);
    $section->add(N_('User Groups'), [
        'url' => 'icingadb/usergroups',
        'priority' => 70
    ]);
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

    // TODO: Switch to from='ipl' prior release!
    $this->requireCssFile('balls.less', 'ipldev');
    $this->requireCssFile('graphs.less', 'ipldev');

    $this->provideCssFile('lists.less');
    $this->provideCssFile('mixins.less');
    $this->provideCssFile('widgets.less');
}
