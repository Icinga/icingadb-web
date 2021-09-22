<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Object;

/**
 * Add a comment to a host or service
 */
class AddCommentCommand extends WithCommentCommand
{
    /**
     * Optional time when the acknowledgement should expire
     *
     * @var int
     */
    protected $expireTime;

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
