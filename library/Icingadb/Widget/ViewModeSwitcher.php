<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

class ViewModeSwitcher extends CompatForm
{

    /** @var string Default view mode */
    const DEFAULT_VIEW_MODE = 'common';

    /** @var string Default view mode param */
    const DEFAULT_VIEW_MODE_PARAM = 'view';

    /** @var array View mode-icon pairs */
    public static $viewModes = [
        'minimal'  => 'minimal',
        'common'   => 'default',
        'detailed' => 'detailed'
    ];

    /** @var Url */
    protected $url;

    protected $method = 'POST';

    /** @var string */
    protected $viewModeParam = self::DEFAULT_VIEW_MODE_PARAM;

    protected $defaultAttributes = ['class' => 'view-mode-switcher'];

    public function __construct(Url $url)
    {
        $this->url = $url;
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
        if ( null !== $this->url->getParam( $this->getViewModeParam() )) {
            return $this->url->getParam($this->getViewModeParam());
        }

        if (isset ($_POST[$this->getViewModeParam()])) {
            return $_POST[$this->getViewModeParam()];
        }

        return static::DEFAULT_VIEW_MODE;
    }

    protected function assemble()
    {
        $viewModeParam = $this->getViewModeParam();
        $currentViewMode = $this->getViewMode();

        foreach (static::$viewModes as $viewMode => $icon) {

            $input = new HtmlElement('input', [
                'class'   => 'autosubmit',
                'type' => 'radio',
                'name' => $viewModeParam,
                'value' => $viewMode,
                'id' => $icon
            ]);

            $label = new HtmlElement('label', [
                    'for' => $icon,
                ], $viewMode
            );

            if ($viewMode === $currentViewMode) {
                $input->getAttributes()->add('checked', '');
            }

            $this->add([ $input, $label]);
        }
    }
}
