<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Module\Icingadb\Widget\BaseListItem;

abstract class BaseOrderedListItem extends BaseListItem
{
    /** @var int This element's position */
    protected $order;

    /**
     * Set this element's position
     *
     * @param int $order
     *
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = (int) $order;

        return $this;
    }

    /**
     * Get this element's position
     *
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }
}
