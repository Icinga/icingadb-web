<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Web\Control;

use ipl\Html\Form;
use ipl\I18n\Translation;
use ipl\Web\Common\FormUid;

class TimestampToggle extends Form
{
    use FormUid;
    use Translation;

    /** @var string Default timestamp mode param */
    public const DEFAULT_TIMESTAMP_MODE_PARAM = 'timestamps';

    /** @var bool Whether relative or absolute timestamps are to be used */
    protected bool $enabled;

    protected $defaultAttributes = [
        'name'    => 'timestamp-toggle',
        'class'   => ['icinga-form', 'icinga-controls', 'inline']
    ];

    /**
     * Create a toggle to switch between absolute and relative timestamps
     *
     * When enabled, timestamps are displayed as relative, absolute otherwise
     *
     * @param bool $enabled True to use relative timestamps, false for absolute
     */
    public function __construct(bool $enabled = false)
    {
        $this->enabled = $enabled;
        $this->addElementDecoratorLoaderPaths([['ipl\\Web\\Compat\\FormDecorator', 'Decorator']]);
    }

    /**
     * Get the current value of the timestamp-toggle element
     *
     * @return string `absolute` or `relative`
     */
    public function getToggleValue(): string
    {
        return $this->getValue('timestamp-toggle');
    }

    /**
     * Get whether the toggle is enabled
     *
     * When enabled, timestamps are displayed as relative, absolute otherwise
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->getElement('timestamp-toggle')->isChecked();
    }

    protected function assemble(): void
    {
        $this->addElement('checkbox', 'timestamp-toggle', [
            'class'             => ['icingadb-timestamp-toggle', 'autosubmit'],
            'label'             => $this->translate('Use relative timestamps'),
            'value'             => $this->enabled,
            'checkedValue'      => 'relative',
            'uncheckedValue'    => 'absolute',
            'decorators'        => [
                'Label',
                'LabelGroup' => [
                    'name' => 'HtmlTag',
                    'options' => [
                        'tag' => 'div',
                        'class' => 'control-label-group',
                    ]
                ],
                'Checkbox',
                'RenderElement',
                'ControlGroup' => [
                    'name' => 'HtmlTag',
                    'options' => [
                        'tag' => 'div',
                        'class' => 'control-group'
                    ]
                ]
            ]
        ]);
        $this->addElement($this->createUidElement());
    }
}
