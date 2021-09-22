<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\BaseHtmlElement;

/**
 * Visually represents one single check attempt.
 */
class AttemptBall extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'ball'];

    /**
     * Create a new attempt ball
     *
     * @param bool $taken Whether the attempt was taken
     */
    public function __construct(bool $taken = false)
    {
        if ($taken) {
            $this->addAttributes(['class' => 'ball-size-s taken']);
        } else {
            $this->addAttributes(['class' => 'ball-size-xs']);
        }
    }
}
