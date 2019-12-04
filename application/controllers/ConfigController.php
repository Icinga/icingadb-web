<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Application\Config;
use Icinga\Module\Icingadb\Forms\DatabaseConfigForm;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Web\Widget\Tab;
use Icinga\Web\Widget\Tabs;
use ipl\Html\HtmlString;

class ConfigController extends Controller
{
//    public function init()
//    {
//        $this->assertPermission('config/modules');
//
//        parent::init();
//    }

    public function databaseAction()
    {
        $form = (new DatabaseConfigForm())
            ->setIniConfig(Config::module('icingadb'));

        $form->handleRequest();

        $this->mergeTabs($this->Module()->getConfigTabs()->activate('database'));

        $this->addContent(new HtmlString($form->render()));
    }

    protected function mergeTabs(Tabs $tabs)
    {
        /** @var Tab $tab */
        foreach ($tabs->getTabs() as $tab) {
            $this->tabs->add($tab->getName(), $tab);
        }

        return $this;
    }
}
