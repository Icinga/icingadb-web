<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Stdlib\BaseFilter;
use ipl\Stdlib\Filter;
use ipl\Web\Url;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBadge;

abstract class StateBadges extends BaseHtmlElement
{
    use BaseFilter;

    /** @var object $item */
    protected $item;

    /** @var string */
    protected $type;

    /** @var string Prefix */
    protected $prefix;

    /** @var ?Url Badge link */
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
        $this->type = $this->getType();
        $this->prefix = $this->getPrefix();
        $this->url = $this->getBaseUrl();
    }

    /**
     * Get the type of the items
     *
     * @return string
     */
    abstract protected function getType(): string;

    /**
     * Get the prefix for accessing state information
     *
     * @return string
     */
    abstract protected function getPrefix(): string;

    /**
     * Get the badge base URL
     *
     * @return ?Url
     */
    protected function getBaseUrl(): ?Url
    {
        return null;
    }

    /**
     * Get the integer of the given state text
     *
     * @param string $state
     *
     * @return int
     *
     * @throws InvalidArgumentException if the given state is not valid
     */
    protected function getStateInt(string $state): int
    {
        throw new InvalidArgumentException('%s is not a valid state', $state);
    }

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
     * @param mixed $content
     * @param ?Filter\Rule $filter
     *
     * @return Link
     */
    public function createLink($content, Filter\Rule $filter = null): Link
    {
        $url = clone $this->getUrl();

        $urlFilter = Filter::all();
        if ($filter !== null) {
            $urlFilter->add($filter);
        }

        if ($this->hasBaseFilter()) {
            $urlFilter->add($this->getBaseFilter());
        }

        if (! $urlFilter->isEmpty()) {
            $url->setFilter($urlFilter);
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
    protected function createBadge(string $state): ?BaseHtmlElement
    {
        $key = $this->prefix . "_{$state}";

        if (isset($this->item->$key) && $this->item->$key) {
            $stateBadge = new StateBadge($this->item->$key, $state);

            if ($this->url !== null) {
                $this->createLink(
                    $stateBadge,
                    Filter::equal($this->type . '.state.soft_state', $this->getStateInt($state))
                );
            }

            return new HtmlElement('li', null, $stateBadge);
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
    protected function createGroup(string $state): ?BaseHtmlElement
    {
        $content = [];
        $handledKey = $this->prefix . "_{$state}_handled";
        $unhandledKey = $this->prefix . "_{$state}_unhandled";

        if (isset($this->item->$unhandledKey) && $this->item->$unhandledKey) {
            $unhandledStateBadge = new StateBadge($this->item->$unhandledKey, $state);

            if ($this->url !== null) {
                $unhandledStateBadge = $this->createLink(
                    $unhandledStateBadge,
                    Filter::all(
                        Filter::equal($this->type . '.state.soft_state', $this->getStateInt($state)),
                        Filter::equal($this->type . '.state.is_handled', 'n'),
                        Filter::equal($this->type . '.state.is_reachable', 'y')
                    )
                );
            }

            $content[] = new HtmlElement('li', null, $unhandledStateBadge);
        }

        if (isset($this->item->$handledKey) && $this->item->$handledKey) {
            $handledStateBadge = new StateBadge($this->item->$handledKey, $state, true);

            if ($this->url !== null) {
                $handledStateBadge = $this->createLink(
                    $handledStateBadge,
                    Filter::all(
                        Filter::equal($this->type . '.state.soft_state', $this->getStateInt($state)),
                        Filter::any(
                            Filter::equal($this->type . '.state.is_handled', 'y'),
                            Filter::equal($this->type . '.state.is_reachable', 'n')
                        )
                    )
                );
            }

            $content[] = new HtmlElement('li', null, $handledStateBadge);
        }

        if (empty($content)) {
            return null;
        }

        return Html::tag('li', Html::tag('ul', $content));
    }
}
