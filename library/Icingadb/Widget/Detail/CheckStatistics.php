<?php

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Widget\Card;
use Icinga\Module\Icingadb\Widget\CheckAttempt;
use Icinga\Module\Icingadb\Widget\TimeAgo;
use Icinga\Module\Icingadb\Widget\TimeUntil;
use Icinga\Module\Icingadb\Widget\VerticalKeyValue;
use Icinga\Util\Format;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\StateBall;

class CheckStatistics extends Card
{
    protected $object;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'check-statistics'];

    public function __construct($object)
    {
        $this->object = $object;
    }

    protected function assembleBody(BaseHtmlElement $body)
    {
        $lastUpdate = Html::tag(
            'li', new VerticalKeyValue('Last update', new TimeAgo($this->object->state->last_update))
        );
        $nextCheck = Html::tag(
            'li', new VerticalKeyValue('Next check', new TimeUntil($this->object->state->next_check))
        );
        $now = Html::tag('li', ['class' => 'above'], Html::tag('strong', 'Now'));
        $timeline = Html::tag('div', ['class' => 'check-timeline']);

        if ($this->object->state->is_overdue) {
            $timeline->add(Html::tag('ol', [
                $lastUpdate,
                $nextCheck,
                $now
            ]));
            $timeline->addAttributes(['class' => 'overdue']);
        } else {
            $timeline->add(Html::tag('ol', [
                $lastUpdate,
                $now,
                $nextCheck
            ]));
        }

        $body->add([
            $timeline,
            new VerticalKeyValue('Check interval', Format::seconds($this->object->check_interval))
        ]);
    }

    protected function assembleFooter(BaseHtmlElement $footer)
    {
    }

    protected function assembleHeader(BaseHtmlElement $header)
    {
        $checkSource = [
            new StateBall($this->object->state->is_reachable ? 'up' : 'down', StateBall::SIZE_MEDIUM),
            ' ',
            $this->object->state->check_source
        ];

        $header->add([
            new VerticalKeyValue('Command', $this->object->checkcommand),
            new VerticalKeyValue('Attempts', new CheckAttempt($this->object->state->attempt, $this->object->max_check_attempts)),
            new VerticalKeyValue('Check source', $checkSource),
            new VerticalKeyValue('Execution time', Format::seconds($this->object->state->execution_time)),
            new VerticalKeyValue('Latency', Format::seconds($this->object->state->latency)),
        ]);
    }
}
