<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

trait NoSubjectLink
{
    /** @var bool */
    protected $noSubjectLink = false;

    /**
     * Set whether a list item's subject should be a link
     *
     * @param bool $state
     *
     * @return $this
     */
    public function setNoSubjectLink($state = true)
    {
        $this->noSubjectLink = $state;

        return $this;
    }

    /**
     * Get whether a list item's subject should be a link
     *
     * @return bool
     */
    public function getNoSubjectLink()
    {
        return $this->noSubjectLink;
    }
}
