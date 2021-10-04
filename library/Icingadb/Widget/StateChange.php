<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Web\Widget\StateBall;

class StateChange extends BaseHtmlElement
{
    protected $previousState;

    protected $state;

    protected $currentStateBallSize = StateBall::SIZE_BIG;

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
            new StateBall($this->state, $this->currentStateBallSize)
        ]);
    }

    /**
     * Set the state ball size for the current state
     *
     * @param string $size
     *
     * @return $this
     */
    public function setCurrentStateBallSize(string $size): self
    {
        $this->currentStateBallSize = $size;

        return $this;
    }
}
