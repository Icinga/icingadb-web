<?php

namespace Icinga\Module\Eagle\Widget;

use Icinga\Module\Eagle\Model\Host;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

/**
 * Host item of a host list. Represents one database row.
 */
class HostListItem extends BaseHtmlElement
{
    protected $tag = 'li';

    /** @var Host The item's associated host model */
    protected $host;

    /** @var \Icinga\Module\Eagle\Model\HostState The state of the host */
    protected $state;

    /**
     * Create a new host list item
     *
     * @param Host $host The item's associated host model
     */
    public function __construct(Host $host)
    {
        $this->host = $host;
        $this->state = $host->state;
    }

    protected function createHeader()
    {
        $header = [
            $this->createTitle(),
            $this->createMeta()
        ];

        return Html::tag('header', $header);
    }

    protected function createMain()
    {
        return Html::tag('div', ['class' => 'main'], [
            $this->createTitle(),
            Html::tag('p', ['class' => 'caption output'], $this->state->output)
        ]);
    }

    protected function createMeta()
    {
        return Html::tag('div', ['class' => 'meta'], new TimeSince($this->host->state->last_update));
    }

    protected function createTitle()
    {
        return HTML::tag('div', ['class' => 'title'], [
            $this->host->display_name,
            ' is ',
            $this->state->getStateTextTranslated()
        ]);
    }

    protected function createVisual()
    {
        return Html::tag('div', ['class' => 'visual'], [
            new StateBall($this->state->getStateText(), StateBall::SIZE_LARGE),
            new CheckAttempt($this->state->attempt, $this->host->max_check_attempts)
        ]);
    }

    protected function assemble()
    {
        $this->add($this->createVisual());
        $this->add($this->createMain());
    }
}
