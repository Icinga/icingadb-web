<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Exception;
use Icinga\Application\Config;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\ConfirmRemovalForm;
use Icinga\Module\Icingadb\Forms\DatabaseConfigForm;
use Icinga\Module\Icingadb\Forms\RedisConfigForm;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Monitoring\Forms\Config\SecurityConfigForm;
use Icinga\Module\Monitoring\Forms\Config\TransportConfigForm;
use Icinga\Module\Monitoring\Forms\Config\TransportReorderForm;
use Icinga\Web\Form;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Tab;
use Icinga\Web\Widget\Tabs;
use ipl\Html\HtmlString;
use ipl\Web\Widget\ButtonLink;

class ConfigController extends Controller
{
//    public function init()
//    {
//        $this->assertPermission('config/modules');
//
//        parent::init();
//    }

    public function commandTransportsAction()
    {
        $form = new TransportReorderForm();

        $form->handleRequest();

        $this->mergeTabs($this->Module()->getConfigTabs()->activate('command-transports'));

        $this->addControl(
            (new ButtonLink(
                $this->translate('Create Command Transport'),
                'icingadb/config/create-command-transport',
                'plus'
            ))->setBaseTarget('_next')
        );

        $this->addFormToContent($form);
    }

    public function createCommandTransportAction()
    {
        $this->setTitle($this->translate('Create Command Transport'));

        $form = (new TransportConfigForm())
            ->setIniConfig(Config::module('monitoring', 'commandtransports'))
            ->setRedirectUrl('icingadb/config/command-transports');

        $form->setOnSuccess(function (TransportConfigForm $form) {
            try {
                $form->add($form::transformEmptyValuesToNull($form->getValues()));
            } catch (Exception $e) {
                $form->error($e->getMessage());

                return false;
            }

            if ($form->save()) {
                $this->translate('Command transport successfully created');

                return true;
            }

            return false;
        });

        $form->handleRequest();

        $this->addFormToContent($form);
    }

    public function deleteCommandTransportAction()
    {
        $this->setTitle($this->translate('Delete Command Transport'));

        $transportName = $this->params->getRequired('transport');

        $transportConfigForm = (new TransportConfigForm())
            ->setIniConfig(Config::module('monitoring', 'commandtransports'));

        $confirmRemovalForm = (new ConfirmRemovalForm())
            ->setRedirectUrl('icingadb/config/command-transports');

        $confirmRemovalForm->setOnSuccess(
            function (ConfirmRemovalForm $form) use ($transportName, $transportConfigForm) {
                try {
                    $transportConfigForm->delete($transportName);
                } catch (Exception $e) {
                    $form->error($e->getMessage());

                    return false;
                }

                if ($transportConfigForm->save()) {
                    Notification::success(sprintf(
                        $this->translate('Command transport "%s" successfully removed'),
                        $transportName
                    ));

                    return true;
                }

                return false;
            }
        );

        $confirmRemovalForm->handleRequest();

        $this->addFormToContent($confirmRemovalForm);
    }

    public function updateCommandTransportAction()
    {
        $this->setTitle($this->translate('Update Command Transport'));

        $transportName = $this->params->getRequired('transport');

        $form = (new TransportConfigForm())
            ->setIniConfig(Config::module('monitoring', 'commandtransports'))
            ->setRedirectUrl('icingadb/config/command-transports');

        $form->setOnSuccess(function (TransportConfigForm $form) use ($transportName) {
            try {
                $form->edit($transportName, array_map(
                    function ($v) {
                        return $v !== '' ? $v : null;
                    },
                    $form->getValues()
                ));
            } catch (Exception $e) {
                $form->error($e->getMessage());

                return false;
            }

            if ($form->save()) {
                Notification::success(sprintf(
                    $this->translate('Command transport "%s" successfully updated'),
                    $transportName
                ));

                return true;
            }

            return false;
        });

        try {
            $form->load($transportName);
            $form->handleRequest();
        } catch (NotFoundError $_) {
            $this->httpNotFound(sprintf($this->translate('Command transport "%s" not found'), $transportName));
        }

        $this->addFormToContent($form);
    }

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

    public function securityAction()
    {
        $form = (new SecurityConfigForm())
            ->setIniConfig(Config::module('monitoring'));

        $form->handleRequest();

        $this->mergeTabs($this->Module()->getConfigTabs()->activate('security'));

        $this->addFormToContent($form);
    }

    protected function addFormToContent(Form $form)
    {
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
