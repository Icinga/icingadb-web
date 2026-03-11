<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Command\Transport;

use Icinga\Exception\IcingaException;

/**
 * Exception thrown if a command was not sent
 */
class CommandTransportException extends IcingaException
{
    /** @var mixed The command that was not sent */
    private mixed $command = null;

    /**
     * Set the command that was not sent
     *
     * This will be passed to the next transport in the chain.
     * Make sure the transport accepts this type in {@see CommandTransportInterface::send()}.
     *
     * @param mixed $command
     *
     * @return $this
     */
    public function setCommand(mixed $command): static
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Get the command that was not sent
     *
     * @return mixed
     */
    public function getCommand(): mixed
    {
        return $this->command;
    }
}
