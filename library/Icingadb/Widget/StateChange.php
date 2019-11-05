<?php

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Web\Widget\StateBall;

class StateChange extends BaseHtmlElement
{
    protected $previousState;

    protected $state;

    protected $defaultAttributes = ['class' => 'state-change'];

    protected $tag = 'div';

    public function __construct($state, $previousState)
    {
        $this->previousState = $previousState;
        $this->state = $state;
    }

    protected function assemble()
    {
        $this->add([
            new StateBall($this->previousState, StateBall::SIZE_BIG),
            new StateBall($this->state, StateBall::SIZE_BIG)
        ]);
    }
}
