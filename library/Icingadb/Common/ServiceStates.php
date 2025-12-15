<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

/**
 * Collection of possible service states.
 */
class ServiceStates
{
    public const OK = 0;

    public const WARNING = 1;

    public const CRITICAL = 2;

    public const UNKNOWN = 3;

    public const PENDING = 99;

    /**
     * Get the integer value of the given textual service state
     *
     * @param string $state
     *
     * @return int
     *
     * @throws \InvalidArgumentException If the given service state is invalid, i.e. not known
     */
    public static function int(string $state): int
    {
        switch (strtolower($state)) {
            case 'ok':
                $int = self::OK;
                break;
            case 'warning':
                $int = self::WARNING;
                break;
            case 'critical':
                $int = self::CRITICAL;
                break;
            case 'unknown':
                $int = self::UNKNOWN;
                break;
            case 'pending':
                $int = self::PENDING;
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid service state %d', $state));
        }

        return $int;
    }

    /**
     * Get the textual representation of the passed service state
     *
     * @param int|null $state
     *
     * @return string
     *
     * @throws \InvalidArgumentException If the given service state is invalid, i.e. not known
     */
    public static function text(int $state = null): string
    {
        switch (true) {
            case $state === self::OK:
                $text = 'ok';
                break;
            case $state === self::WARNING:
                $text = 'warning';
                break;
            case $state === self::CRITICAL:
                $text = 'critical';
                break;
            case $state === self::UNKNOWN:
                $text = 'unknown';
                break;
            case $state === self::PENDING:
                $text = 'pending';
                break;
            case $state === null:
                $text = 'not-available';
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid service state %d', $state));
        }

        return $text;
    }

    /**
     * Get the translated textual representation of the passed service state
     *
     * @param int|null $state
     *
     * @return string
     *
     * @throws \InvalidArgumentException If the given service state is invalid, i.e. not known
     */
    public static function translated(int $state = null): string
    {
        switch (true) {
            case $state === self::OK:
                $text = t('ok');
                break;
            case $state === self::WARNING:
                $text = t('warning');
                break;
            case $state === self::CRITICAL:
                $text = t('critical');
                break;
            case $state === self::UNKNOWN:
                $text = t('unknown');
                break;
            case $state === self::PENDING:
                $text = t('pending');
                break;
            case $state === null:
                $text = t('not available');
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid service state %d', $state));
        }

        return $text;
    }
}
