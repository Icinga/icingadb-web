<?php

namespace Icinga\Module\Eagle\Widget;

use Icinga\Date\DateFormatter;
use Icinga\Module\Eagle\Model\Host;
use Icinga\Web\Form\Element\Time;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

/**
 * Host item of a host list. Represents one database row.
 */
class HostListItemCompact extends BaseHtmlElement
{
    protected $tag = 'li';

    protected $defaultAttributes = [ 'class' => 'list-item'];

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
            Html::tag('p', ['class' => 'caption output'], $this->state->output),
            $this->createTimestamp(),
        ];

        return Html::tag('header', $header);
    }

    protected function createTimestamp() {
        return Html::tag('time', [
            'datetime' => DateFormatter::formatDateTime($this->state->last_update)
        ], DateFormatter::timeSince($this->state->last_update)
        );
    }

    protected function createMain()
    {
        return Html::tag('div', ['class' => 'main'], [
            $this->createHeader(),
        ]);
    }

    protected function createTitle()
    {
        return HTML::tag('div', ['class' => 'title'], [
            Html::tag('a', ['href' => 'host', 'class' => 'object'], $this->host->display_name),
            ' is ',
            Html::tag('span', ['class' => 'state'], $this->state->getStateTextTranslated())
        ]);
    }

    protected function createVisual()
    {
        return Html::tag('div', ['class' => 'visual '], [
            new StateBall($this->state->getStateText(), StateBall::SIZE_LARGE),
            new CheckAttempt($this->state->attempt, $this->host->max_check_attempts)
        ]);
    }

    protected function assemble()
    {
        if ($this->state->next_update < time()) {
            $this->addAttributes(['class' => 'late']);
        }
        $this->add([
            $this->createVisual(),
            $this->createMain(),
        ]);
    }
}
