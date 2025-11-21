<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

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
