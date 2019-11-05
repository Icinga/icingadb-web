<?php

namespace Icinga\Module\Eagle\Widget\Detail;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class EventBox extends BaseHtmlElement
{
    /** @var mixed */
    protected $caption;

    /** @var bool */
    protected $captionIsPluginOutput;

    /** @var string */
    protected $state;

    /** @var mixed */
    protected $timestamp;

    /** @var mixed */
    protected $title;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'event-box collapsible'];

    /**
     * @return string
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * @param string $caption
     * @param bool   $isPluginOutput
     *
     * @return $this
     */
    public function setCaption($caption, $isPluginOutput = false)
    {
        $this->caption = $caption;
        $this->captionIsPluginOutput = $isPluginOutput;

        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
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
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param mixed $timestamp
     *
     * @return $this
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    protected function assemble()
    {
        if ($this->state !== null) {
            $this->addAttributes(['class' => "state-{$this->state}"]);
        }

        $header = [];

        if ($this->title !== null) {
            $header[] = Html::tag('h3', ['class' => 'title'], $this->title);
        }

        if ($this->timestamp !== null) {
            $header[] = $this->timestamp;
        }

        if (! empty($header)) {
            $this->add(Html::tag('header', $header));
        }

        if ($this->caption !== null) {
            $this->add(Html::tag(
                'section',
                ['class' => 'caption' . ($this->captionIsPluginOutput ? ' output' : '' )],
                $this->caption
            ));
        }
    }
}
