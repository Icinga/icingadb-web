<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Application\Hook;
use Icinga\Application\Hook\TicketHook;

trait TicketLinks
{
    /** @var bool Whether the ticket link is disabled */
    protected $ticketLinkDisabled = false;

    /**
     * Set whether the ticket link is disabled
     *
     * @param bool $state
     *
     * @return $this
     */
    public function setTicketLinkDisabled(bool $state = true): self
    {
        $this->ticketLinkDisabled = $state;

        return $this;
    }

    /**
     * Get whether the ticket link is disabled
     *
     * @return bool
     */
    public function isTicketLinkDisabled(): bool
    {
        return $this->ticketLinkDisabled;
    }

    /**
     * Get whether list items should render host and service links
     *
     * @return string
     */
    public function createTicketLinks($text): string
    {
        if ($this->isTicketLinkDisabled() || ! Hook::has('ticket')) {
            return $text ?? '';
        }

        /** @var TicketHook $tickets */
        $tickets = Hook::first('ticket');

        return $tickets->createLinks($text);
    }
}
