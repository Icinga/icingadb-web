<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Object;

/**
 * Remove a problem acknowledgement from a host or service
 */
class RemoveAcknowledgementCommand extends ObjectsCommand
{
    use CommandAuthor;
}
