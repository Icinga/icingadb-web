<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Command\Object;

/**
 * Delete a host or service comment
 */
class DeleteCommentCommand extends ObjectsCommand
{
    use CommandAuthor;
}
