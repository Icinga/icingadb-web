<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Date\DateFormatter;
use ipl\Html\BaseHtmlElement;

/**
 * @TODO(el): Move to ipl-web
 */
class TimeSince extends BaseHtmlElement
{
    /** @var int */
    protected $since;

    protected $tag = 'time';

    protected $defaultAttributes = ['class' => 'time-since'];

    public function __construct($since)
    {
        $this->since = (int) $since;
    }

    protected function assemble()
    {
        $dateTime = DateFormatter::formatDateTime($this->since);

        $this->addAttributes([
            'datetime' => $dateTime,
            'title'    => $dateTime
        ]);

        $this->add(DateFormatter::timeSince($this->since));
    }
}
