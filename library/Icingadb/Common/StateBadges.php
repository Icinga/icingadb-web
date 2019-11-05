<?php

namespace Icinga\Module\Icingadb\Common;

use Icinga\Module\Icingadb\Widget\StateBadge;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

abstract class StateBadges extends BaseHtmlElement
{
    /** @var object $item */
    protected $item;

    /** @var string Prefix */
    protected $prefix;

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
    }

    abstract protected function getPrefix();

    protected function createBadge($state)
    {
        $key = $this->prefix . "_{$state}";

        if (isset($this->item->$key) && $this->item->$key) {
            return Html::tag('li', new StateBadge($this->item->$key, $state));
        }

        return null;
    }

    protected function createGroup($state)
    {
        $content = [];
        $handledKey = $this->prefix . "_{$state}_handled";
        $unhandledKey = $this->prefix . "_{$state}_unhandled";

        if (isset($this->item->$unhandledKey) && $this->item->$unhandledKey) {
            $content[] = Html::tag('li', new StateBadge($this->item->$unhandledKey, $state));
        }

        if (isset($this->item->$handledKey) && $this->item->$handledKey) {
            $content[] = Html::tag('li', new StateBadge($this->item->$handledKey, $state, true));
        }

        if (empty($content)) {
            return null;
        }

        return Html::tag('li', Html::tag('ul', $content));
    }
}
