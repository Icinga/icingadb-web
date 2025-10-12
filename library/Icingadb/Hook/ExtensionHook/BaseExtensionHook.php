<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Hook\ExtensionHook;

use Icinga\Module\Icingadb\Hook\Common\HookUtils;

abstract class BaseExtensionHook
{
    use HookUtils;

    /** @var int Used as default return value for {@see BaseExtensionHook::getLocation()} */
    public const IDENTIFY_LOCATION_BY_SECTION = -1;

    /** @var string Output section, right at the top */
    public const OUTPUT_SECTION = 'output';

    /** @var string Graph section, below output */
    public const GRAPH_SECTION = 'graph';

    /** @var string Detail section, below graphs */
    public const DETAIL_SECTION = 'detail';

    /** @var string Action section, below action and note urls */
    public const ACTION_SECTION = 'action';

    /** @var string Problem section, below comments and downtimes */
    public const PROBLEM_SECTION = 'problem';

    /** @var string Related section, below groups and notification recipients */
    public const RELATED_SECTION = 'related';

    /** @var string State section, below check statistics and performance data */
    public const STATE_SECTION = 'state';

    /** @var string Config section, below custom variables and feature toggles */
    public const CONFIG_SECTION = 'config';

    /**
     * Base locations for all known sections
     *
     * @var array<string, int>
     */
    public const BASE_LOCATIONS = [
        self::OUTPUT_SECTION    => 1000,
        self::GRAPH_SECTION     => 1100,
        self::DETAIL_SECTION    => 1200,
        self::ACTION_SECTION    => 1300,
        self::PROBLEM_SECTION   => 1400,
        self::RELATED_SECTION   => 1500,
        self::STATE_SECTION     => 1600,
        self::CONFIG_SECTION    => 1700
    ];

    /** @var int This hook's location */
    private $location = self::IDENTIFY_LOCATION_BY_SECTION;

    /** @var string This hook's section */
    private $section = self::DETAIL_SECTION;

    /**
     * Set this hook's location
     *
     * Note that setting the location explicitly may override other widgets using the same location. But beware that
     * this applies to this hook's widget as well.
     *
     * Also, while the sections are guaranteed to always refer to the same general location, this guarantee is lost
     * when setting a location explicitly. The core and base locations may change at any time and any explicitly set
     * location will **not** adjust accordingly.
     *
     * @param int $location
     *
     * @return void
     */
    final public function setLocation(int $location)
    {
        $this->location = $location;
    }

    /**
     * Get this hook's location
     *
     * @return int
     */
    final public function getLocation(): int
    {
        return $this->location;
    }

    /**
     * Set this hook's section
     *
     * Sections are used to place widgets loosely in a general location. Using e.g. the `state` section this hook's
     * widget will always appear after the check statistics and performance data widgets.
     *
     * @param string $section
     *
     * @return void
     */
    final public function setSection(string $section)
    {
        $this->section = $section;
    }

    /**
     * Get this hook's section
     *
     * @return string
     */
    final public function getSection(): string
    {
        return $this->section;
    }

    /**
     * Union both arrays and sort the result by key
     *
     * @param array $coreElements
     * @param array $extensions
     *
     * @return array
     */
    final public static function injectExtensions(array $coreElements, array $extensions): array
    {
        $extensions += $coreElements;

        uksort($extensions, function ($a, $b) {
            if ($a < 1000 && $b >= 1000) {
                $b -= 1000;
                if (abs($a - $b) < 10 && abs($a % 100 - $b % 100) < 10) {
                    return -1;
                }
            } elseif ($b < 1000 && $a >= 1000) {
                $a -= 1000;
                if (abs($a - $b) < 10 && abs($a % 100 - $b % 100) < 10) {
                    return 1;
                }
            }

            return $a < $b ? -1 : ($a > $b ? 1 : 0);
        });

        return $extensions;
    }
}
