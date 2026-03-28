<?php

/* Icinga DB Web | (c) 2026 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Control;

use ipl\I18n\Translation;

/**
 * View mode switcher with an additional tabular view mode
 */
class TabularViewModeSwitcher extends ViewModeSwitcher
{
    use Translation;

    public static $viewModes = [
        'minimal'  => 'minimal',
        'common'   => 'default',
        'detailed' => 'detailed',
        'tabular'  => 'tabular'
    ];

    protected function getTitle(string $viewMode): string
    {
        if ($viewMode === 'tabular') {
            return $this->getViewMode() === $viewMode
                ? $this->translate('Tabular view active')
                : $this->translate('Switch to tabular view');
        } else {
            return parent::getTitle($viewMode);
        }
    }
}
