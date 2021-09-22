<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\BaseHtmlElement;

class StateBadge extends BaseHtmlElement
{
    protected $defaultAttributes = ['class' => 'state-badge'];

    /** @var mixed Badge content */
    protected $content;

    /** @var bool Whether the state is handled */
    protected $isHandled;

    /** @var string Textual representation of a state */
    protected $state;

    /**
     * Create a new state badge
     *
     * @param mixed  $content   Content of the badge
     * @param string $state     Textual representation of a state
     * @param bool   $isHandled True if state is handled
     */
    public function __construct($content, string $state, bool $isHandled = false)
    {
        $this->content = $content;
        $this->isHandled = $isHandled;
        $this->state = $state;
    }

    protected function assemble()
    {
        $this->setTag('span');

        $class = "state-{$this->state}";
        if ($this->isHandled) {
            $class .= ' handled';
        }

        $this->addAttributes(['class' => $class]);

        $this->add($this->content);
    }
}
