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

    protected function assemble()
    {
        $viewModeParam = $this->getViewModeParam();

        $this->addElement($this->createUidElement());
        $this->addElement(new HiddenElement($viewModeParam));

        foreach (static::$viewModes as $viewMode => $icon) {
            $protectedId = $this->protectId('grid-view-mode-switcher-' . $icon);
            $input = new InputElement($viewModeParam, [
                'class' => 'autosubmit',
                'id'    => $protectedId,
                'name'  => $viewModeParam,
                'type'  => 'radio',
                'value' => $viewMode
            ]);
            $input->getAttributes()->registerAttributeCallback('checked', function () use ($viewMode) {
                return $viewMode === $this->getViewMode();
            });

            $label = new HtmlElement(
                'label',
                Attributes::create([
                    'for' => $protectedId
                ]),
                new IcingaIcon($icon)
            );
            $label->getAttributes()->registerAttributeCallback('title', function () use ($viewMode) {
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
            });

            $this->addHtml($input, $label);
        }
    }
}
