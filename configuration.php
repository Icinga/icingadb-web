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

    // TODO: Switch to from='ipl' prior release!
    $this->requireCssFile('balls.less', 'ipldev');

    $this->provideCssFile('mixins.less');
    $this->provideCssFile('lists.less');
}
