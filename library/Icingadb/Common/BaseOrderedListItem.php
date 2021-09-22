<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

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
    public function setOrder(int $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get this element's position
     *
     * @return int
     */
    public function getOrder()
    {
        if ($this->order === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->order;
    }
}
