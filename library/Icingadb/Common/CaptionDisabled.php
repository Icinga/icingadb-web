<?php

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
