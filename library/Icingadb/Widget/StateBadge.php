<?php

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\HtmlDocument;
use ipl\Web\Widget\Link;

class StateBadge extends HtmlDocument
{
    /** @var mixed Badge link */
    protected $link;

    /** @var bool Whether the state is handled */
    protected $isHandled;

    /** @var string Textual representation of a state */
    protected $state;

    /**
     * Create a new state badge
     *
     * @param mixed  $link   Link of the badge
     * @param string $state     Textual representation of a state
     * @param bool   $isHandled True if state is handled
     */
    public function __construct(Link $link, $state, $isHandled = false)
    {
        $this->link = $link;
        $this->isHandled = $isHandled;
        $this->state = $state;
    }

    protected function assemble()
    {
        $class = "state-badge state-{$this->state}";
        if ($this->isHandled) {
            $class .= ' handled';
        }

        $this->link->addAttributes(['class' => $class]);

        $this->add($this->link);
    }
}
