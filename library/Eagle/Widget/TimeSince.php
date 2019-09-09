<?php

namespace Icinga\Module\Eagle\Widget;

use Icinga\Date\DateFormatter;
use ipl\Html\BaseHtmlElement;

/**
 * @TODO(el): Move to ipl-web
 */
class TimeSince extends BaseHtmlElement
{
    protected $tag = 'span';

    protected $defaultAttributes = ['class' => 'time-since'];

    public function __construct($since)
    {
        $this->setContent(DateFormatter::timeSince((int) $since));
    }
}
