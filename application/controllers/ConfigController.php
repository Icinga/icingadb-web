<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Application\Config;
use Icinga\Module\Icingadb\Forms\GeneralConfigForm;
use Icinga\Module\Icingadb\Forms\RedisConfigForm;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Web\Form;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Tab;
use Icinga\Web\Widget\Tabs;
use ipl\Html\HtmlString;

class ConfigController extends Controller
{
    public function init()
    {
        $this->assertPermission('config/modules');

        parent::init();
    }

    public function generalSettingsAction()
    {
        $this->mergeTabs($this->Module()->getConfigTabs()->activate('general-settings'));

        $form = (new GeneralConfigForm(Config::module('icingadb')))
            ->on(GeneralConfigForm::ON_SUBMIT, function (GeneralConfigForm $form) {
                Notification::success($this->translate('New configuration settings have been saved.'));

                $this->redirectNow('icingadb/config/general-settings');
            })
            ->handleRequest($this->getServerRequest());

        $this->addContent($form);
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
