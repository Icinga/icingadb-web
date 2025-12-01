<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Command\Transport;

use Icinga\Module\Icingadb\Command\IcingaCommand;

/**
 * Interface for Icinga command transports
 */
interface CommandTransportInterface
{
    /**
     * Send an Icinga command over the Icinga command transport
     *
     * @param IcingaCommand $command The command to send
     * @param ?int $now Timestamp of the command or null for now
     *
     * @throws CommandTransportException If sending the Icinga command failed
     */
    public function send(IcingaCommand $command, ?int $now = null);
}
