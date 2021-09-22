<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Data\Filter\Filter;
use Icinga\Module\Icingadb\Widget\StateBadge;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;
use ipl\Web\Widget\Link;

abstract class StateBadges extends BaseHtmlElement
{
    use BaseFilter;

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
    abstract protected function getBaseUrl(): Url;

    /**
     * Get the prefix for accessing state information
     *
     * @return string
     */
    abstract protected function getPrefix(): string;

    /**
     * Get the integer of the given state text
     *
     * @param string $state
     *
     * @return int
     */
    abstract protected function getStateInt(string $state): int;

    /**
     * Get the badge URL
     *
     * @return Url
     */
    public function getUrl(): Url
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
    public function setUrl(Url $url): self
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
    public function createLink($content, array $params = null): Link
    {
        $url = clone $this->getUrl();

        if (! empty($params)) {
            $url->getParams()->mergeValues($params);
        }

        if ($this->hasBaseFilter()) {
            $url->addFilter(Filter::fromQueryString(QueryString::render($this->getBaseFilter())));
        }

        return new Link($content, $url);
    }

    /**
     * Create a state bade
     *
     * @param string $state
     *
     * @return ?BaseHtmlElement
     */
    protected function createBadge(string $state)
    {
        $key = $this->prefix . "_{$state}";

        if (isset($this->item->$key) && $this->item->$key) {
            return Html::tag('li', $this->createLink(
                new StateBadge($this->item->$key, $state),
                ['state.soft_state' => $this->getStateInt($state)]
            ));
        }

        return null;
    }

    /**
     * Create a state group
     *
     * @param string $state
     *
     * @return ?BaseHtmlElement
     */
    protected function createGroup(string $state)
    {
        $content = [];
        $handledKey = $this->prefix . "_{$state}_handled";
        $unhandledKey = $this->prefix . "_{$state}_unhandled";

        if (isset($this->item->$unhandledKey) && $this->item->$unhandledKey) {
            $content[] = Html::tag('li', $this->createLink(
                new StateBadge($this->item->$unhandledKey, $state),
                ['state.soft_state' => $this->getStateInt($state), 'state.is_handled' => 'n']
            ));
        }

        if (isset($this->item->$handledKey) && $this->item->$handledKey) {
            $content[] = Html::tag('li', $this->createLink(
                new StateBadge($this->item->$handledKey, $state, true),
                ['state.soft_state' => $this->getStateInt($state), 'state.is_handled' => 'y']
            ));
        }

        if (empty($content)) {
            return null;
        }

        return Html::tag('li', Html::tag('ul', $content));
    }
}
