<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

trait ObjectLinkDisabled
{
    /** @var bool */
    protected $objectLinkDisabled = false;

    /**
     * Set whether list items should render host and service links
     *
     * @param bool $state
     *
     * @return $this
     */
    public function setObjectLinkDisabled($state = true)
    {
        $this->objectLinkDisabled = $state;

        return $this;
    }

    /**
     * Get whether list items should render host and service links
     *
     * @return bool
     */
    public function getObjectLinkDisabled()
    {
        return $this->objectLinkDisabled;
    }
}
