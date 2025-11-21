<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Command\Object;

/**
 * Send custom notifications for a host or service
 */
class SendCustomNotificationCommand extends WithCommentCommand
{
    /**
     * Whether the notification is forced
     *
     * Forced notifications are sent out regardless of time restrictions and whether or not notifications are enabled.
     *
     * @var bool
     */
    protected $forced;

    /**
     * Get whether to force the notification
     *
     * @return ?bool
     */
    public function getForced()
    {
        return $this->forced;
    }

    /**
     * Set whether to force the notification
     *
     * @param   bool $forced
     *
     * @return  $this
     */
    public function setForced(bool $forced = true): self
    {
        $this->forced = $forced;

        return $this;
    }
}
