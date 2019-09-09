<?php

namespace Icinga\Module\Eagle\Widget;

use ipl\Html\BaseHtmlElement;

/**
 * Visually represents one single check attempt.
 */
class AttemptBall extends BaseHtmlElement
{
    protected $tag = 'div';

    /**
     * Create a new attempt ball
     *
     * @param bool $taken Whether the attempt was taken
     */
    public function __construct($taken = false)
    {
        $class = 'attempt-ball';

        if ($taken) {
            $class .= ' taken';
        }

        $this->defaultAttributes = ['class' => $class];
    }
}
