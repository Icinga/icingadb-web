<?php

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
     * Get the textual representation of the passed host state
     *
     * @param int $state
     *
     * @return string
     *
     * @throws \InvalidArgumentException If the given host state is invalid, i.e. not known
     */
    public static function text($state)
    {
        switch ((int) $state) {
            case self::UP:
                $text = 'up';
                break;
            case self::DOWN:
                $text = 'down';
                break;
            case self::UNREACHABLE:
                $text = 'unreachable';
                break;
            case self::PENDING:
                $text = 'pending';
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid host state %d', $state));
        }

        return $text;
    }

    /**
     * Get the translated textual representation of the passed host state
     *
     * @param int $state
     *
     * @return string
     *
     * @throws \InvalidArgumentException If the given host state is invalid, i.e. not known
     */
    public static function translated($state)
    {
        switch ((int) $state) {
            case self::UP:
                $text = mt('icingadb', 'up');
                break;
            case self::DOWN:
                $text = mt('icingadb', 'down');
                break;
            case self::UNREACHABLE:
                $text = mt('icingadb', 'unreachable');
                break;
            case self::PENDING:
                $text = mt('icingadb', 'pending');
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid host state %d', $state));
        }

        return $text;
    }
}
