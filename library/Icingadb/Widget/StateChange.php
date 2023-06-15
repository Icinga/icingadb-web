<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

class StateChange extends BaseHtmlElement
{
    protected $previousState;

    protected $state;

    protected $previousStateBallSize = StateBall::SIZE_BIG;

    protected $currentStateBallSize = StateBall::SIZE_BIG;

    protected $defaultAttributes = ['class' => 'state-change'];

    protected $tag = 'div';

    /** @var ?Icon Current state ball icon */
    protected $icon;

    /** @var bool Whether the state is handled */
    protected $isHandled = false;

    public function __construct(string $state, string $previousState)
    {
        $this->previousState = $previousState;
        $this->state = $state;
    }

    /**
     * Set the state ball size for the previous state
     *
     * @param string $size
     *
     * @return $this
     */
    public function setPreviousStateBallSize(string $size): self
    {
        $this->previousStateBallSize = $size;

        return $this;
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

    /**
     * Set the current state ball icon
     *
     * @param $icon
     *
     * @return $this
     */
    public function setIcon($icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Set whether the current state is handled
     *
     * @return $this
     */
    public function setHandled($isHandled = true): self
    {
        $this->isHandled = $isHandled;

        return $this;
    }

    protected function assemble()
    {
        $currentStateBall = (new StateBall($this->state, $this->currentStateBallSize))
            ->add($this->icon);

        if ($this->isHandled) {
            $currentStateBall->getAttributes()->add('class', 'handled');
        }

        $previousStateBall = new StateBall($this->previousState, $this->previousStateBallSize);
        if ($this->isRightBiggerThanLeft()) {
            $this->getAttributes()->add('class', 'reversed-state-balls');

            $this->addHtml($currentStateBall, $previousStateBall);
        } else {
            $this->addHtml($previousStateBall, $currentStateBall);
        }
    }

    protected function isRightBiggerThanLeft(): bool
    {
        $left = $this->previousStateBallSize;
        $right = $this->currentStateBallSize;

        if ($left === $right) {
            return false;
        } elseif ($left === StateBall::SIZE_LARGE) {
            return false;
        }

        $map = [
            StateBall::SIZE_BIG => [false, [StateBall::SIZE_LARGE]],
            StateBall::SIZE_MEDIUM_LARGE => [false, [StateBall::SIZE_BIG, StateBall::SIZE_LARGE]],
            StateBall::SIZE_MEDIUM => [true, [StateBall::SIZE_TINY, StateBall::SIZE_SMALL]],
            StateBall::SIZE_SMALL => [true, [StateBall::SIZE_TINY]]
        ];

        list($negate, $sizes) = $map[$left];
        $found = in_array($right, $sizes, true);

        return ($negate && ! $found) || (! $negate && $found);
    }
}
