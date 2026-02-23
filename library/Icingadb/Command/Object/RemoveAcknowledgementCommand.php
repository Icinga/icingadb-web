<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Command\Object;

/**
 * Remove a problem acknowledgement from a host or service
 */
class RemoveAcknowledgementCommand extends ObjectsCommand
{
    use CommandAuthor;
}
