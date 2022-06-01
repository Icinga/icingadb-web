<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use ipl\Stdlib\Filter\Rule;

/**
 * @deprecated Use {@see \ipl\Stdlib\BaseFilter} instead. This will be removed with version 1.1
 */
trait BaseFilter
{
    /** @var Rule Base filter */
    private $baseFilter;

    /**
     * Get whether a base filter has been set
     *
     * @return bool
     */
    public function hasBaseFilter(): bool
    {
        return $this->baseFilter !== null;
    }

    /**
     * Get the base filter
     *
     * @return ?Rule
     */
    public function getBaseFilter()
    {
        return $this->baseFilter;
    }

    /**
     * Set the base filter
     *
     * @param Rule $baseFilter
     *
     * @return $this
     */
    public function setBaseFilter(Rule $baseFilter = null): self
    {
        $this->baseFilter = $baseFilter;

        return $this;
    }
}
