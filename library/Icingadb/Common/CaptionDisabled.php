<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

trait CaptionDisabled
{
    protected $captionDisabled = false;

    /**
     * @return bool
     */
    public function isCaptionDisabled()
    {
        return $this->captionDisabled;
    }

    /**
     * @param bool $captionDisabled
     *
     * @return $this
     */
    public function setCaptionDisabled($captionDisabled = true)
    {
        $this->captionDisabled = $captionDisabled;

        return $this;
    }
}
