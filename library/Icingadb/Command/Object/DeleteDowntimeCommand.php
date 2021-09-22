<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Object;

use Icinga\Module\Icingadb\Command\IcingaCommand;

/**
 * Delete a host or service downtime
 */
class DeleteDowntimeCommand extends IcingaCommand
{
    use CommandAuthor;

    /**
     * Name of the downtime (Icinga 2.4+)
     *
     * Required for removing the downtime via Icinga 2's API.
     *
     * @var string
     */
    protected $downtimeName;

    /**
     * Get the name of the downtime (Icinga 2.4+)
     *
     * Required for removing the downtime via Icinga 2's API.
     *
     * @return string
     */
    public function getDowntimeName(): string
    {
        if ($this->downtimeName === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->downtimeName;
    }

    /**
     * Set the name of the downtime (Icinga 2.4+)
     *
     * Required for removing the downtime via Icinga 2's API.
     *
     * @param   string  $downtimeName
     *
     * @return  $this
     */
    public function setDowntimeName(string $downtimeName): self
    {
        $this->downtimeName = $downtimeName;

        return $this;
    }
}
