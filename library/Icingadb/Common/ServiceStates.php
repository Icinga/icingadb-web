<?php

namespace Icinga\Module\Icingadb\Common;

/**
 * Collection of possible service states.
 */
class ServiceStates
{
    const OK = 0;

    const WARNING = 1;

    const CRITICAL = 2;

    const UNKNOWN = 3;

    const PENDING = 99;

    /**
     * Get the textual representation of the passed service state
     *
     * @param int $state
     *
     * @return string
     *
     * @throws \InvalidArgumentException If the given service state is invalid, i.e. not known
     */
    public static function text($state)
    {
        switch ((int) $state) {
            case self::OK:
                $text = 'ok';
                break;
            case self::WARNING:
                $text = 'warning';
                break;
            case self::CRITICAL:
                $text = 'critical';
                break;
            case self::UNKNOWN:
                $text = 'unkown';
                break;
            case self::PENDING:
                $text = 'pending';
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid service state %d', $state));
        }

        return $text;
    }

    /**
     * Get the translated textual representation of the passed service state
     *
     * @param int $state
     *
     * @return string
     *
     * @throws \InvalidArgumentException If the given service state is invalid, i.e. not known
     */
    public static function translated($state)
    {
        switch ((int) $state) {
            case self::OK:
                $text = mt('icingadb', 'ok');
                break;
            case self::WARNING:
                $text = mt('icingadb', 'warning');
                break;
            case self::CRITICAL:
                $text = mt('icingadb', 'critical');
                break;
            case self::UNKNOWN:
                $text = mt('icingadb', 'unknown');
                break;
            case self::PENDING:
                $text = mt('icingadb', 'pending');
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid service state %d', $state));
        }

        return $text;
    }
}
