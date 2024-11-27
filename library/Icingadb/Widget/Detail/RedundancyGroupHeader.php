<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Model\RedundancyGroupSummary;
use Icinga\Module\Icingadb\Widget\DependencyNodeStatistics;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\StateBall;

/**
 * @property RedundancyGroup $object
 */
class RedundancyGroupHeader extends BaseObjectHeader
{
    /** @var RedundancyGroupSummary */
    protected $summary;

    public function __construct(RedundancyGroup $object, RedundancyGroupSummary $summary)
    {
        $this->summary = $summary;

        parent::__construct($object);
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $stateBall = new StateBall($this->object->state->getStateText(), $this->getStateBallSize());
        $stateBall->add($this->object->state->getIcon());

        $visual->addHtml($stateBall);
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $subject = $this->createSubject();
        if ($this->object->state->failed) {
            $title->addHtml(Html::sprintf(
                $this->translate('%s has no working objects', '<groupname> has ...'),
                $subject
            ));
        } else {
            $title->addHtml(Html::sprintf(
                $this->translate('%s has working objects', '<groupname> has ...'),
                $subject
            ));
        }
    }

    protected function createStatistics(): BaseHtmlElement
    {
        return new DependencyNodeStatistics($this->summary);
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->add($this->createTitle());
        $header->add($this->createStatistics());
        $header->add($this->createTimestamp());
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->add($this->createHeader());
    }
}
