<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Navigation;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Macros;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Web\Navigation\NavigationItem;
use ipl\Web\Url;

class Action extends NavigationItem
{
    use Auth;
    use Macros;

    /**
     * Whether this action's macros were already resolved
     *
     * @var bool
     */
    protected $resolved = false;

    /**
     * This action's object
     *
     * @var Host|Service
     */
    protected $object;

    /**
     * The filter to use when being asked whether to render this action
     *
     * @var string
     */
    protected $filter;

    /**
     * This action's raw url attribute
     *
     * @var string
     */
    protected $rawUrl;

    /**
     * Set this action's object
     *
     * @param  Host|Service $object
     *
     * @return $this
     */
    public function setObject($object): self
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Get this action's object
     *
     * @return Host|Service
     */
    protected function getObject()
    {
        return $this->object;
    }

    /**
     * Set the filter to use when being asked whether to render this action
     *
     * @param   string  $filter
     *
     * @return  $this
     */
    public function setFilter(string $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Get the filter to use when being asked whether to render this action
     *
     * @return  ?string
     */
    public function getFilter(): ?string
    {
        return $this->filter;
    }

    /**
     * Set this item's url
     *
     * @param \Icinga\Web\Url|string $url
     *
     * @return $this
     */
    public function setUrl($url): self
    {
        if (is_string($url)) {
            $this->rawUrl = $url;
        } else {
            parent::setUrl($url);
        }

        return $this;
    }

    public function getUrl(): ?\Icinga\Web\Url
    {
        $url = parent::getUrl();
        if (! $this->resolved && $url === null && $this->rawUrl !== null) {
            $this->setUrl(Url::fromPath($this->expandMacros($this->rawUrl, $this->getObject())));
            $this->resolved = true;
            return parent::getUrl();
        } else {
            return $url;
        }
    }

    public function getRender(): bool
    {
        if ($this->render === null) {
            $filter = $this->getFilter();
            $this->render = ! $filter || $this->isMatchedOn($filter, $this->getObject());
        }

        return $this->render;
    }
}
