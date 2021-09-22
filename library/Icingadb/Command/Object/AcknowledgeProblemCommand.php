<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Object;

/**
 * Acknowledge a host or service problem
 */
class AcknowledgeProblemCommand extends WithCommentCommand
{
    /**
     * Whether the acknowledgement is sticky
     *
     * Sticky acknowledgements remain until the host or service recovers. Non-sticky acknowledgements will be
     * automatically removed when the host or service state changes.
     *
     * @var bool
     */
    protected $sticky = false;

    /**
     * Whether to send a notification about the acknowledgement

     * @var bool
     */
    protected $notify = false;

    /**
     * Whether the comment associated with the acknowledgement is persistent
     *
     * Persistent comments are not lost the next time the monitoring host restarts.
     *
     * @var bool
     */
    protected $persistent = false;

    /**
     * Optional time when the acknowledgement should expire
     *
     * @var int
     */
    protected $expireTime;

    /**
     * Set whether the acknowledgement is sticky
     *
     * @param   bool $sticky
     *
     * @return  $this
     */
    public function setSticky(bool $sticky = true): self
    {
        $this->sticky = $sticky;

        return $this;
    }

    /**
     * Is the acknowledgement sticky?
     *
     * @return bool
     */
    public function getSticky(): bool
    {
        return $this->sticky;
    }

    /**
     * Set whether to send a notification about the acknowledgement
     *
     * @param   bool $notify
     *
     * @return  $this
     */
    public function setNotify(bool $notify = true): self
    {
        $this->notify = $notify;

        return $this;
    }

    /**
     * Get whether to send a notification about the acknowledgement
     *
     * @return bool
     */
    public function getNotify(): bool
    {
        return $this->notify;
    }

    /**
     * Set whether the comment associated with the acknowledgement is persistent
     *
     * @param   bool $persistent
     *
     * @return  $this
     */
    public function setPersistent(bool $persistent = true): self
    {
        $this->persistent = $persistent;

        return $this;
    }

    /**
     * Is the comment associated with the acknowledgement is persistent?
     *
     * @return bool
     */
    public function getPersistent(): bool
    {
        return $this->persistent;
    }

    /**
     * Set the time when the acknowledgement should expire
     *
     * @param   int $expireTime
     *
     * @return  $this
     */
    public function setExpireTime(int $expireTime): self
    {
        $this->expireTime = $expireTime;

        return $this;
    }

    /**
     * Get the time when the acknowledgement should expire
     *
     * @return ?int
     */
    public function getExpireTime()
    {
        return $this->expireTime;
    }
}
