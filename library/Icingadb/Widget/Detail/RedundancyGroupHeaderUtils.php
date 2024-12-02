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
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeSince;

trait RedundancyGroupHeaderUtils
{
    use Translation;

    /**
     * Get the object
     *
     * @return RedundancyGroup
     */
    abstract protected function getObject(): RedundancyGroup;

    /**
     * Get the summary
     *
     * @return RedundancyGroupSummary
     */
    abstract protected function getSummary(): RedundancyGroupSummary;

    /**
     * Create the subject
     *
     * @return ValidHtml
     */
    abstract protected function createSubject(): ValidHtml;

    /**
     * Get the state ball size
     *
     * @return string
     */
    abstract protected function getStateBallSize(): string;

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new StateBall($this->getObject()->state->getStateText(), $this->getStateBallSize()));
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

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $caption->addHtml(new DependencyNodeStatistics($this->getSummary()));
    }

    protected function createTimestamp(): BaseHtmlElement
    {
        return new TimeSince($this->getObject()->state->last_state_change->getTimestamp());
    }
}
