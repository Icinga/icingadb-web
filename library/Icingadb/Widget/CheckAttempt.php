<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\FormattedString;

/**
 * Visually represents the check attempts taken out of max check attempts.
 */
class CheckAttempt extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'check-attempt'];

    /** @var int Current attempt */
    protected $attempt;

    /** @var int Max check attempts */
    protected $maxAttempts;

    /**
     * Create a new check attempt widget
     *
     * @param int $attempt     Current check attempt
     * @param int $maxAttempts Max check attempts
     */
    public function __construct($attempt, $maxAttempts)
    {
        $this->attempt = $attempt;
        $this->maxAttempts = $maxAttempts;
    }

    protected function assemble()
    {
        if ($this->attempt == $this->maxAttempts) {
            return;
        }

        if ($this->maxAttempts > 5) {
            $this->add(new FormattedString('%d/%d', $this->attempt, $this->maxAttempts));
        } else {
            for ($i = 0; $i < $this->attempt; ++$i) {
                $this->add(new AttemptBall(true));
            }
            for ($i = $this->attempt; $i < $this->maxAttempts; ++$i) {
                $this->add(new AttemptBall());
            }
        }
    }
}
