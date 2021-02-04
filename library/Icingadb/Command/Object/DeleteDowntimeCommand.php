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
    public function getDowntimeName()
    {
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
    public function setDowntimeName($downtimeName)
    {
        $this->downtimeName = $downtimeName;

        return $this;
    }
}
