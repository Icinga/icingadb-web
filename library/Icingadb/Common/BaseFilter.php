<?php

namespace Icinga\Module\Icingadb\Common;

use Icinga\Data\Filter\Filter;

trait BaseFilter
{
    /** @var Filter Base filter */
    private $baseFilter;

    /**
     * Get whether a base filter has been set
     *
     * @return bool
     */
    public function hasBaseFilter()
    {
        return $this->baseFilter !== null;
    }

    /**
     * Get the base filter
     *
     * @return Filter
     */
    public function getBaseFilter()
    {
        return $this->baseFilter;
    }

    /**
     * Set the base filter
     *
     * @param Filter $baseFilter
     *
     * @return $this
     */
    public function setBaseFilter(Filter $baseFilter = null)
    {
        if ($baseFilter === null) {
            $this->baseFilter = null;
        } elseif (! $baseFilter->isEmpty()) {
            $this->baseFilter = $baseFilter;
        }

        return $this;
    }
}
