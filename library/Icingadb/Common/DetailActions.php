<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use ipl\Html\BaseHtmlElement;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;

trait DetailActions
{
    /** @var bool */
    protected $detailActionsDisabled = false;

    /**
     * Set whether this list should be an action-list
     *
     * @param bool $state
     *
     * @return $this
     */
    public function setDetailActionsDisabled(bool $state = true): self
    {
        $this->detailActionsDisabled = $state;

        return $this;
    }

    /**
     * Get whether this list should be an action-list
     *
     * @return bool
     */
    public function getDetailActionsDisabled(): bool
    {
        return $this->detailActionsDisabled;
    }

    /**
     * Prepare this list as action-list
     *
     * @return $this
     */
    public function initializeDetailActions(): self
    {
        $this->getAttributes()
            ->registerAttributeCallback('class', function () {
                return $this->getDetailActionsDisabled() ? null : 'action-list';
            })
            ->registerAttributeCallback('data-icinga-multiselect-count-label', function () {
                return $this->getDetailActionsDisabled() ? null : t('%d Item(s) selected');
            });

        return $this;
    }

    /**
     * Set the url to use for multiple selected list items
     *
     * @param Url $url
     *
     * @return $this
     */
    protected function setMultiselectUrl(Url $url): self
    {
        /** @var BaseHtmlElement $this */
        $this->getAttributes()
            ->registerAttributeCallback('data-icinga-multiselect-url', function () use ($url) {
                return $this->getDetailActionsDisabled() ? null : (string) $url;
            });

        return $this;
    }

    /**
     * Set the url to use for a single selected list item
     *
     * @param Url $url
     *
     * @return $this
     */
    protected function setDetailUrl(Url $url): self
    {
        /** @var BaseHtmlElement $this */
        $this->getAttributes()
            ->registerAttributeCallback('data-icinga-detail-url', function () use ($url) {
                return $this->getDetailActionsDisabled() ? null : (string) $url;
            });

        return $this;
    }

    /**
     * Associate the given element with the given multi-selection filter
     *
     * @param BaseHtmlElement $element
     * @param Filter\Rule $filter
     *
     * @return $this
     */
    public function addMultiselectFilterAttribute(BaseHtmlElement $element, Filter\Rule $filter): self
    {
        $element->getAttributes()
            ->registerAttributeCallback('data-icinga-multiselect-filter', function () use ($filter) {
                return $this->getDetailActionsDisabled() ? null : '(' . QueryString::render($filter) . ')';
            });

        return $this;
    }

    /**
     * Associate the given element with the given single-selection filter
     *
     * @param BaseHtmlElement $element
     * @param Filter\Rule $filter
     *
     * @return $this
     */
    public function addDetailFilterAttribute(BaseHtmlElement $element, Filter\Rule $filter): self
    {
        $element->getAttributes()
            ->registerAttributeCallback('data-icinga-detail-filter', function () use ($filter) {
                return $this->getDetailActionsDisabled() ? null : '(' . QueryString::render($filter) . ')';
            });

        return $this;
    }
}
