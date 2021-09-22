<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

abstract class BaseStatusBar extends BaseHtmlElement
{
    use BaseFilter;

    protected $summary;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'status-bar'];

    public function __construct($summary)
    {
        $this->summary = $summary;
    }

    abstract protected function assembleTotal(BaseHtmlElement $total);

    abstract protected function createStateBadges(): BaseHtmlElement;

    protected function createCount(): BaseHtmlElement
    {
        $total = Html::tag('span', ['class' => 'item-count']);

        $this->assembleTotal($total);

        return $total;
    }

    protected function assemble()
    {
        $this->add([
            $this->createCount(),
            $this->createStateBadges()
        ]);
    }
}
