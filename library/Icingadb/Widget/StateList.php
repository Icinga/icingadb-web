<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Common\ViewMode;

abstract class StateList extends BaseItemList
{
    use ViewMode;
    use NoSubjectLink;

    /** @var bool Whether the list contains at least one item with an icon_image */
    protected $hasIconImages = false;

    /**
     * Get whether the list contains at least one item with an icon_image
     *
     * @return bool
     */
    public function hasIconImages()
    {
        return $this->hasIconImages;
    }

    /**
     * Set whether the list contains at least one item with an icon_image
     *
     * @param bool $hasIconImages
     */
    public function setHasIconImages(bool $hasIconImages)
    {
        $this->hasIconImages = $hasIconImages;
    }

    protected function assemble()
    {
        $this->addAttributes(['class' => $this->getViewMode()]);

        parent::assemble();
    }
}
