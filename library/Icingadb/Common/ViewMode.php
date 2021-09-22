<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

trait ViewMode
{
    /** @var string */
    protected $viewMode;

    /**
     * Get the view mode
     *
     * @return ?string
     */
    public function getViewMode()
    {
        return $this->viewMode;
    }

    /**
     * Set the view mode
     *
     * @param string $viewMode
     *
     * @return $this
     */
    public function setViewMode(string $viewMode): self
    {
        $this->viewMode = $viewMode;

        return $this;
    }
}
