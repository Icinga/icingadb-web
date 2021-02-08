<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Module\Icingadb\Widget\BaseItemList;

/**
 * @method BaseOrderedListItem getItemClass()
 */
abstract class BaseOrderedItemList extends BaseItemList
{
    protected $tag = 'ol';

    protected function assemble()
    {
        $itemClass = $this->getItemClass();

        $i = 0;
        foreach ($this->data as $data) {
            $item = new $itemClass($data, $this);
            $item->setOrder($i++);

            $this->add($item);
        }

        if ($this->isEmpty()) {
            $this->setTag('div');
            $this->add(new EmptyState(t('No items found.')));
        }
    }
}
