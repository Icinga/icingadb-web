<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

trait CaptionDisabled
{
    /** @var bool */
    protected $captionDisabled = false;

    /**
     * @return bool
     */
    public function isCaptionDisabled(): bool
    {
        return $this->captionDisabled;
    }

    /**
     * @param bool $captionDisabled
     *
     * @return $this
     */
    public function setCaptionDisabled(bool $captionDisabled = true): self
    {
        $this->captionDisabled = $captionDisabled;

        return $this;
    }
}
