<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Command\Object;

/**
 * Delete a host or service downtime
 */
class DeleteDowntimeCommand extends ObjectsCommand
{
    use CommandAuthor;
}
