<?php

namespace Icinga\Module\Icingadb\Common;

use Icinga\Data\Filter\Filter;
use Icinga\Module\Icingadb\Widget\StateBadge;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Url;
use ipl\Web\Widget\Link;

abstract class StateBadges extends BaseHtmlElement
{
    /** @var Filter Base filter applied to any badge link */
    protected $baseFilter;

    /** @var object $item */
    protected $item;

    /** @var string Prefix */
    protected $prefix;

    /** @var Url Badge link */
    protected $url;

    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'state-badges'];

    /**
     * Create a new widget for state badges
     *
     * @param object $item
     */
    public function __construct($item)
    {
        $this->item = $item;
        $this->prefix = $this->getPrefix();
        $this->url = $this->getBaseUrl();
    }

    /**
     * Get the badge base URL
     *
     * @return Url
     */
    abstract protected function getBaseUrl();

    /**
     * Get the prefix for accessing state information
     *
     * @return string
     */
    abstract protected function getPrefix();

    /**
     * Get the integer of the given state text
     *
     * @param string $state
     *
     * @return int
     */
    abstract protected function getStateInt($state);

    /**
     * Get whether a base filter is configured
     *
     * @return bool
     */
    public function hasBaseFilter()
    {
        return $this->baseFilter !== null;
    }

    /**
     * Get the base filter applied to any badge link
     *
     * @return Filter
     */
    public function getBaseFilter()
    {
        return $this->baseFilter;
    }

    /**
     * Set the base filter applied to any badge link
     *
     * @param Filter $baseFilter
     *
     * @return $this
     */
    public function setBaseFilter($baseFilter)
    {
        $this->baseFilter = $baseFilter;

        return $this;
    }

    /**
     * Get the badge URL
     *
     * @return Url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the badge URL
     *
     * @param Url $url
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Create a badge link
     *
     * @param       $content
     * @param array $params
     *
     * @return Link
     */
    public function createLink($content, array $params = null)
    {
        $url = clone $this->getUrl();

        if (! empty($params)) {
            $url->getParams()->mergeValues($params);
        }

        if ($this->hasBaseFilter()) {
            $url->addFilter($this->getBaseFilter());
        }

        return new Link($content, $url);
    }

    protected function createBadge($state)
    {
        $key = $this->prefix . "_{$state}";

        if (isset($this->item->$key) && $this->item->$key) {
            return Html::tag('li', new StateBadge(
                $this->createLink($this->item->$key, ['state.soft_state' => $this->getStateInt($state)]),
                $state
            ));
        }

        return null;
    }

    protected function createGroup($state)
    {
        $content = [];
        $handledKey = $this->prefix . "_{$state}_handled";
        $unhandledKey = $this->prefix . "_{$state}_unhandled";

        if (isset($this->item->$unhandledKey) && $this->item->$unhandledKey) {
            $content[] = Html::tag('li', new StateBadge(
                $this->createLink($this->item->$unhandledKey, ['state.soft_state' => $this->getStateInt($state)]),
                $state
            ));
        }

        if (isset($this->item->$handledKey) && $this->item->$handledKey) {
            $content[] = Html::tag('li', new StateBadge(
                $this->createLink(
                    $this->item->$handledKey,
                    ['state.soft_state' => $this->getStateInt($state), 'state.is_handled' => 'y']
                ),
                $state,
                true
            ));
        }

        if (empty($content)) {
            return null;
        }

        return Html::tag('li', Html::tag('ul', $content));
    }
}
