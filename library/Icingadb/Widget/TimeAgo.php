<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Date\DateFormatter;
use ipl\Html\BaseHtmlElement;

/**
 * @TODO(el): Move to ipl-web
 */
class TimeAgo extends BaseHtmlElement
{
    /** @var int */
    protected $ago;

    protected $tag = 'time';

    protected $defaultAttributes = ['class' => 'time-ago'];

    public function __construct($ago)
    {
        $this->ago = (int) $ago;
    }

    protected function assemble()
    {
        $dateTime = DateFormatter::formatDateTime($this->ago);

        $this->addAttributes([
            'datetime' => $dateTime,
            'title'    => $dateTime
        ]);

        $this->add(DateFormatter::timeAgo($this->ago));
    }
}
