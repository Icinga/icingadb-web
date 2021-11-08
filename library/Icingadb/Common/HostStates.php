<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

/**
 * Collection of possible host states.
 */
class HostStates
{
    const UP = 0;

    const DOWN = 1;

    const UNREACHABLE = 2;

    const PENDING = 99;

    /**
     * Get the integer value of the given textual host state
     *
     * @param string $state
     *
     * @return int
     *
     * @throws \InvalidArgumentException If the given host state is invalid, i.e. not known
     */
    public static function int(string $state): int
    {
        switch (strtolower($state)) {
            case 'up':
                $int = self::UP;
                break;
            case 'down':
                $int = self::DOWN;
                break;
            case 'unreachable':
                $int = self::UNREACHABLE;
                break;
            case 'pending':
                $int = self::PENDING;
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid host state %d', $state));
        }

        return $int;
    }

    /**
     * Get the textual representation of the passed host state
     *
     * @param int|null $state
     *
     * @return string
     *
     * @throws \InvalidArgumentException If the given host state is invalid, i.e. not known
     */
    public static function text(int $state = null): string
    {
        switch (true) {
            case $state === self::UP:
                $text = 'up';
                break;
            case $state === self::DOWN:
                $text = 'down';
                break;
            case $state === self::UNREACHABLE:
                $text = 'unreachable';
                break;
            case $state === self::PENDING:
                $text = 'pending';
                break;
            case $state === null:
                $text = 'not-available';
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid host state %d', $state));
        }

        return $text;
    }

    /**
     * Get the translated textual representation of the passed host state
     *
     * @param int|null $state
     *
     * @return string
     *
     * @throws \InvalidArgumentException If the given host state is invalid, i.e. not known
     */
    public static function translated(int $state = null): string
    {
        switch (true) {
            case $state === self::UP:
                $text = t('up');
                break;
            case $state === self::DOWN:
                $text = t('down');
                break;
            case $state === self::UNREACHABLE:
                $text = t('unreachable');
                break;
            case $state === self::PENDING:
                $text = t('pending');
                break;
            case $state === null:
                $text = t('not available');
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid host state %d', $state));
        }

        return $text;
    }
}
