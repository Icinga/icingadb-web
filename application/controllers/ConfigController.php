<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Application\Config;
use Icinga\Module\Icingadb\Forms\DatabaseConfigForm;
use Icinga\Module\Icingadb\Forms\RedisConfigForm;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Web\Form;
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

        $this->addFormToContent($form);
    }

    public function redisAction()
    {
        $form = (new RedisConfigForm())
            ->setIniConfig($this->Config());

        $form->handleRequest();

        $this->mergeTabs($this->Module()->getConfigTabs()->activate('redis'));

        $this->addFormToContent($form);
    }

    protected function addFormToContent(Form $form)
    {
        $this->addContent(new HtmlString($form->render()));
    }

    protected function mergeTabs(Tabs $tabs): self
    {
        /** @var Tab $tab */
        foreach ($tabs->getTabs() as $tab) {
            $this->tabs->add($tab->getName(), $tab);
        }

        return $this;
    }
}
