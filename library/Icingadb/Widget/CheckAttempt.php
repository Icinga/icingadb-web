<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\FormattedString;
use ipl\Web\Widget\Ball;

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
    public function __construct(int $attempt, int $maxAttempts)
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
            $this->add(FormattedString::create('%d/%d', $this->attempt, $this->maxAttempts));
        } else {
            for ($i = 0; $i < $this->attempt; ++$i) {
                $this->add(new Ball(Ball::SIZE_SMALL));
            }
            for ($i = $this->attempt; $i < $this->maxAttempts; ++$i) {
                $this->add(new Ball(Ball::SIZE_TINY));
            }
        }
    }
}
