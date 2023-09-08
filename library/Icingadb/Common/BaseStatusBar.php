<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Module\Icingadb\Model\HoststateSummary;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Orm\Model;
use ipl\Stdlib\BaseFilter;

abstract class BaseStatusBar extends BaseHtmlElement
{
    use BaseFilter;

    /** @var ServicestateSummary|HoststateSummary */
    protected $summary;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'status-bar'];

    /**
     * Create a host or service status bar
     *
     * @param ServicestateSummary|HoststateSummary $summary
     */
    public function __construct($summary)
    {
        $this->summary = $summary;
    }

    abstract protected function assembleTotal(BaseHtmlElement $total): void;

    abstract protected function createStateBadges(): BaseHtmlElement;

    protected function createCount(): BaseHtmlElement
    {
        $total = Html::tag('span', ['class' => 'item-count']);

        $this->assembleTotal($total);

        return $total;
    }

    protected function assemble(): void
    {
        $this->add([
            $this->createCount(),
            $this->createStateBadges()
        ]);
    }
}
