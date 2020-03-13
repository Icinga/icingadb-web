<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Compat;

use Icinga\Application\Icinga;
use Icinga\Exception\ProgrammingError;
use ipl\Html\HtmlString;
use Zend_View_Helper_PluginOutput;

class CompatPluginOutput
{
    private static $instance;

    private $pluginOutputhelper;

    /**
     * @return static
     *
     * @throws ProgrammingError
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            require_once Icinga::app()->getModuleManager()->getModule('monitoring')->getBaseDir()
                . '/application/views/helpers/PluginOutput.php';

            $helper = new Zend_View_Helper_PluginOutput();
            $helper->view = Icinga::app()->getViewRenderer()->view;

            $instance = new static();
            $instance->pluginOutputhelper = $helper;

            static::$instance = $instance;
        }

        return static::$instance;
    }

    /**
     * @param string $output
     *
     * @return HtmlString
     */
    public function render($output)
    {
        return new HtmlString($this->pluginOutputhelper->pluginOutput($output));
    }
}
