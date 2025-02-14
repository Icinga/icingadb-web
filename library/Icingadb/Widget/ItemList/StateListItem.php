<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\State;
use Icinga\Module\Icingadb\Widget\Detail\HostAndServiceHeaderUtils;
use ipl\Web\Common\BaseListItem;

/**
 * Host or service item of a host or service list. Represents one database row.
 *
 * @property Host|Service $item
 */
abstract class StateListItem extends BaseListItem
{
    use HostAndServiceHeaderUtils;

    /** @var StateList The list where the item is part of */
    protected $list;

    /** @var State The state of the item */
    protected $state;

    protected function init(): void
    {
        $this->setObject($this->item);
        $this->state = $this->item->state;

        if (isset($this->item->icon_image->icon_image)) {
            $this->list->setHasIconImages(true);
        }
    }

    protected function wantIconImage(): bool
    {
        return $this->list->hasIconImages();
    }

    protected function assemble(): void
    {
        if ($this->state->is_overdue) {
            $this->addAttributes(['class' => 'overdue']);
        }

        $this->add([
            $this->createVisual(),
            $this->createIconImage(),
            $this->createMain()
        ]);
    }
}
