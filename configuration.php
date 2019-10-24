<?php

namespace Icinga\Module\Eagle
{
    /** @var \Icinga\Application\Modules\Module $this */
    $section = $this->menuSection(N_('Icinga DB'), [
        'icon'     => 'database',
        'priority' => 30
    ]);

    $section->add(N_('Hosts'), [
        'url' => 'eagle/hosts',
        'priority' => 10
    ]);
    $section->add(N_('Services'), [
        'url' => 'eagle/services',
        'priority' => 20
    ]);
    $section->add(N_('Downtimes'), [
        'url' => 'eagle/downtimes',
        'priority' => 30
    ]);
    $section->add(N_('Comments'), [
        'url' => 'eagle/comments',
        'priority' => 40
    ]);
    $section->add(N_('Notifications'), [
        'url' => 'eagle/notifications',
        'priority' => 50
    ]);
    $section->add(N_('Users'), [
        'url' => 'eagle/users',
        'priority' => 60
    ]);
    $section->add(N_('User Groups'), [
        'url' => 'eagle/usergroups',
        'priority' => 70
    ]);

    // TODO: Switch to from='ipl' prior release!
    $this->requireCssFile('balls.less', 'ipldev');

    $this->provideCssFile('mixins.less');
    $this->provideCssFile('lists.less');
}
