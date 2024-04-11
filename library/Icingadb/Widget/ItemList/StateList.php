<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\DetailActions;
use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Common\ViewMode;
use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use Icinga\Module\Icingadb\Widget\Notice;
use ipl\Html\HtmlDocument;
use ipl\Web\Common\BaseItemList;

abstract class StateList extends BaseItemList
{
    use ViewMode;
    use NoSubjectLink;
    use DetailActions;

    /** @var bool Whether the list contains at least one item with an icon_image */
    protected $hasIconImages = false;

    /**
     * Get whether the list contains at least one item with an icon_image
     *
     * @return bool
     */
    public function hasIconImages(): bool
    {
        return $this->hasIconImages;
    }

    /**
     * Set whether the list contains at least one item with an icon_image
     *
     * @param bool $hasIconImages
     *
     * @return $this
     */
    public function setHasIconImages(bool $hasIconImages): self
    {
        $this->hasIconImages = $hasIconImages;

        return $this;
    }

    protected function assemble(): void
    {
        $this->addAttributes(['class' => $this->getViewMode()]);

        parent::assemble();

        if ($this->data instanceof VolatileStateResults && $this->data->isRedisUnavailable()) {
            $this->prependWrapper((new HtmlDocument())->addHtml(new Notice(
                t('Redis is currently unavailable. The shown information might be outdated.')
            )));
        }
    }
}
