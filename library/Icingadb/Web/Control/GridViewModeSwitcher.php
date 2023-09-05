<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Control;

/**
 * View mode switcher to toggle between grid and list view
 */
class GridViewModeSwitcher extends ViewModeSwitcher
{
    /** @var string Default view mode */
    public const DEFAULT_VIEW_MODE = 'list';

    /** @var array View mode-icon pairs */
    public static $viewModes = [
        'list' => 'default',
        'grid' => 'grid'
    ];

    protected function getTitle(string $viewMode): string
    {
        $active = null;
        $inactive = null;
        switch ($viewMode) {
            case 'list':
                $active = t('List view active');
                $inactive = t('Switch to list view');
                break;
            case 'grid':
                $active = t('Grid view active');
                $inactive = t('Switch to grid view');
                break;
        }

        return $viewMode === $this->getViewMode() ? $active : $inactive;
    }
}
