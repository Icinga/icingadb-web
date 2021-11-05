<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Navigation\Renderer;

use Exception;
use Icinga\Application\Logger;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Widget\StateBadge;
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

    protected $linkDisabled;

    abstract protected function fetchProblemsCount();

    abstract protected function getUrl();

    public function getProblemsCount()
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
    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get the state text
     *
     * @return string
     */
    public function getState(): string
    {
        if ($this->state === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->state;
    }

    /**
     * Set the title
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the title
     *
     * @return ?string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function render(NavigationItem $item = null): string
    {
        if ($item === null) {
            $item = $this->getItem();
        }

        $item->setCssClass('badge-nav-item icinga-module module-icingadb');

        $badge = $this->createBadge();

        if ($this->linkDisabled) {
            $badge->addAttributes(['class' => 'disabled']);
            $this->setEscapeLabel(false);
            $label = $this->view()->escape($item->getLabel());
            $item->setLabel($badge . $label);

            return (new HtmlDocument())
                ->add(new HtmlString(parent::render($item)))
                ->render();
        }

        $badge = new Link(
            $badge,
            $this->getUrl(),
            ['title' => $this->getTitle()]
        );

        return (new HtmlDocument())
            ->add(new HtmlString(parent::render($item)))
            ->add($badge)
            ->render();
    }

    protected function createBadge()
    {
        $count = $this->getProblemsCount();

        if ($count) {
            return (new StateBadge($count, $this->getState()))
                    ->addAttributes(['class' => 'badge']);
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

    public function disableLink()
    {
        $this->linkDisabled = true;

        return $this;
    }
}
