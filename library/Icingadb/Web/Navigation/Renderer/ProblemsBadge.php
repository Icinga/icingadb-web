<?php

namespace Icinga\Module\Icingadb\Web\Navigation\Renderer;

use Exception;
use Icinga\Application\Logger;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Web\Navigation\NavigationItem;
use Icinga\Web\Navigation\Renderer\NavigationItemRenderer;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlString;
use ipl\Web\Widget\Link;

abstract class ProblemsBadge extends NavigationItemRenderer
{
    use Database;

    const STATE_CRITICAL = 'critical';
    const STATE_UNKNOWN = 'unknown';

    /** @var int Count cache */
    protected $count;

    /** @var string State text */
    protected $state;

    /** @var string Title */
    protected $title;

    abstract protected function fetchProblemsCount();

    abstract protected function getUrl();

    protected function getProblemsCount()
    {
        if ($this->count === null) {
            try {
                $count = $this->fetchProblemsCount();
            } catch (Exception $e) {
                Logger::debug($e);

                $this->count = 1;

                $this->setState(static::STATE_UNKNOWN);
                $this->setTitle($e->getMessage());

                return $this->count;
            }

            $this->count = $this->round($count);

            $this->setState(static::STATE_CRITICAL);
        }

        return $this->count;
    }

    /**
     * Set the state text
     *
     * @param string $state
     *
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get the state text
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set the title
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function render(NavigationItem $item = null)
    {
        if ($item === null) {
            $item = $this->getItem();
        }

        $item->setCssClass('badge-nav-item');

        return (new HtmlDocument())
            ->add(new HtmlString(parent::render($item)))
            ->add($this->createBadge())
            ->render();
    }

    protected function createBadge()
    {
        $count = $this->getProblemsCount();

        if ($count) {
            return new Link($count, $this->getUrl(), [
                'class' => sprintf('badge state-%s', $this->getState()),
                'title' => $this->getTitle()
            ]);
        }

        return null;
    }

    protected function round($count)
    {
        if ($count > 1000000) {
            $count = round($count, -6) / 1000000 . 'M';
        } elseif ($count > 1000) {
            $count = round($count, -3) / 1000 . 'k';
        }

        return $count;
    }
}
