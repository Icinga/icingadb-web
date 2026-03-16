<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Web\Control;

use ipl\Web\Compat\CompatForm;

class TimestampToggle extends CompatForm
{
    /** @var bool Whether relative or absolute timestamps are to be used */
    protected bool $useRelativeTimestamps;

    protected $defaultAttributes = [
        'name'    => 'Use relative timestamps',
        'class'   => ['icinga-form', 'icinga-controls', 'inline']
    ];

    /**
     * A toggle that allows to switch between absolute and relative timestamps
     *
     * @param bool $useRelativeTimestamps Whether to use relative timestamps
     */
    public function __construct(bool $useRelativeTimestamps = false)
    {
        $this->useRelativeTimestamps = $useRelativeTimestamps;
    }

    /**
     * Get whether relative or absolute timestamps are to be used
     *
     * @return bool
     */
    public function getUseRelativeTimestamps(): bool
    {
        return $this->useRelativeTimestamps;
    }

    protected function assemble()
    {
        $this->addElement('checkbox', 'timestamp-toggle', [
            'class'             => ['timestamp-toggle', 'autosubmit'],
            'label'             => $this->translate('Use relative timestamps'),
            'value'             => $this->useRelativeTimestamps,
            'relative-sample'   => trim(strip_tags((string) new TimeAgo()))
        ]);
    }
}
