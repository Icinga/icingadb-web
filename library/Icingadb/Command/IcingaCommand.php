<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command;

/**
 * Base class for commands sent to an Icinga instance
 */
abstract class IcingaCommand
{
    /**
     * Get the name of the command
     *
     * @return string
     */
    public function getName(): string
    {
        $nsParts = explode('\\', get_called_class());
        return substr_replace(end($nsParts), '', -7);  // Remove 'Command' Suffix
    }
}
