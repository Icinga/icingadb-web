<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
