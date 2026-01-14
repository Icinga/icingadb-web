<?php

/* Icinga DB Web | (c) 2026 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Control;

use Icinga\Application\Icinga;
use ipl\Web\Compat\CompatForm;

class TimestampToggle extends CompatForm
{
    /** @var bool Whether relative or absolute timestamps are to be used */
    protected bool $useRelativeTimestamps;

    protected $defaultAttributes = [
        'name'    => 'Use relative timestamps',
        'class'   => 'icinga-form icinga-controls inline'
    ];

    public function __construct(bool $useRelativeTimestamps = false)
    {
        $this->useRelativeTimestamps = $useRelativeTimestamps;
    }

    protected function assemble()
    {
        $this->addElement('checkbox', 'timestamp-toggle', [
            'class'     => 'timestamp-toggle',
            'id'        => Icinga::app()->getRequest()->protectId('timestamp-toggle'),
            'label'     => $this->translate('Use relative timestamps'),
            'value'     => $this->useRelativeTimestamps
        ]);
    }
}
