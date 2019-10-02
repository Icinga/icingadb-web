<?php

namespace Icinga\Module\Eagle\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

class ViewModeSwitcher extends BaseHtmlElement
{
    const DEFAULT_VIEW_MODE = 'common';

    /** @var array View mode-icon pairs */
    public static $viewModes = [
        'compact'  => 'chat-empty',
        'common'   => 'th-list',
        'detailed' => 'chat'
    ];

    /** @var Url */
    protected $url;

    /** @var string */
    protected $viewModeParam = 'view';

    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'view-mode-switcher'];

    public function __construct(Url $url)
    {
        $this->url = $url;
    }

    /**
     * Get the view mode URL parameter
     *
     * @return string
     */
    public function getViewModeParam()
    {
        return $this->viewModeParam;
    }

    /**
     * Set the view mode URL parameter
     *
     * @param string $viewModeParam
     *
     * @return $this
     */
    public function setViewModeParam($viewModeParam)
    {
        $this->viewModeParam = $viewModeParam;

        return $this;
    }

    protected function assemble()
    {
        $viewModeParam = $this->getViewModeParam();
        $currentViewMode = $this->url->getParam($viewModeParam, static::DEFAULT_VIEW_MODE);

        foreach (static::$viewModes as $viewMode => $icon) {
            $url = $this->url->with($viewModeParam, $viewMode);

            $link = Html::tag('li', new Link(new Icon($icon), $url));

            if ($viewMode === $currentViewMode) {
                $link->getAttributes()->add('class', 'active');
            }

            $this->add($link);
        }
    }
}
