<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Url;
use ipl\Web\Widget\IcingaIcon;
use ipl\Web\Widget\Link;

class ViewModeSwitcher extends BaseHtmlElement
{
    /** @var String Default view mode */
    const DEFAULT_VIEW_MODE = 'common';

    /** @var string Default view mode param */
    const DEFAULT_VIEW_MODE_PARAM = 'view';

    /** @var array View mode-icon pairs */
    public static $viewModes = [
        'minimal'  => 'minimal',
        'common'   => 'default',
        'detailed' => 'detailed'
    ];

    /** @var String */
    protected $defaultViewMode;

    /** @var Url */
    protected $url;

    /** @var string */
    protected $viewModeParam = self::DEFAULT_VIEW_MODE_PARAM;

    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'view-mode-switcher'];

    public function __construct(Url $url)
    {
        $this->url = $url;
    }

    /**
     * Get the default view mode
     *
     * @return String
     */
    public function getDefaultViewMode()
    {
        return $this->defaultViewMode ?: static::DEFAULT_VIEW_MODE;
    }

    /**
     * Set the default view mode
     *
     * @param String $viewMode
     *
     * @return $this
     */
    public function setDefaultViewMode($viewMode)
    {
        $this->defaultViewMode = $viewMode;

        return $this;
    }

    /**
     * Get the base url
     *
     * @return Url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the base url
     *
     * @param Url $url
     *
     * @return $this
     */
    public function setUrl(Url $url)
    {
        $this->url = $url;

        return $this;
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

    /**
     * Get the view mode
     *
     * @return string
     */
    public function getViewMode()
    {
        return $this->url->getParam($this->getViewModeParam(), $this->getDefaultViewMode());
    }

    protected function assemble()
    {
        $viewModeParam = $this->getViewModeParam();
        $currentViewMode = $this->getViewMode();

        foreach (static::$viewModes as $viewMode => $icon) {
            $url = $this->url->with($viewModeParam, $viewMode);

            $link = Html::tag('li', new Link(new IcingaIcon($icon), $url));

            if ($viewMode === $currentViewMode) {
                $link->getAttributes()->add('class', 'active');
            }

            $this->add($link);
        }
    }
}
