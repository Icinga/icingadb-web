<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Navigation\Renderer;

use Icinga\Web\Navigation\Renderer\BadgeNavigationItemRenderer;

class TotalProblemsBadge extends BadgeNavigationItemRenderer
{
    /**
     * Cached count
     *
     * @var int
     */
    protected $count;

    /**
     * State to severity map
     *
     * @var array
     */
    protected static $stateSeverityMap = [
        self::STATE_OK          => 0,
        self::STATE_PENDING     => 1,
        self::STATE_UNKNOWN     => 2,
        self::STATE_WARNING     => 3,
        self::STATE_CRITICAL    => 4,
    ];

    /**
     * Severity to state map
     *
     * @var array
     */
    protected static $severityStateMap = [
        self::STATE_OK,
        self::STATE_PENDING,
        self::STATE_UNKNOWN,
        self::STATE_WARNING,
        self::STATE_CRITICAL
    ];

    public function getCount()
    {
        if ($this->count === null) {
            $countMap = array_fill(0, 5, 0);
            $maxSeverity = 0;
            foreach ($this->getItem()->getChildren() as $child) {
                $renderer = $child->getRenderer();
                if ($renderer instanceof ProblemsBadge) {
                    $count = $renderer->getProblemsCount();
                    if ($count) {
                        $severity = static::$stateSeverityMap[$renderer->getState()];
                        $countMap[$severity] += $count;
                        $maxSeverity = max($maxSeverity, $severity);
                    }
                }
            }
            $this->count = $countMap[$maxSeverity];
            $this->state = static::$severityStateMap[$maxSeverity];
        }

        return $this->count;
    }
}
