<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Config;
use Icinga\Data\Filter\Filter;
use Icinga\Forms\ConfirmRemovalForm;
use Icinga\Module\Icingadb\Command\Transport\CommandTransportConfig;
use Icinga\Module\Icingadb\Forms\ApiTransportForm;
use Icinga\Module\Icingadb\Widget\ItemList\CommandTransportList;
use Icinga\Web\Notification;
use ipl\Html\HtmlString;
use ipl\Web\Widget\ButtonLink;

class CommandTransportController extends ConfigController
{
    public function init()
    {
        $this->assertPermission('config/modules');
    }

    public function indexAction()
    {
        $list = new CommandTransportList((new CommandTransportConfig())->select());

        $this->addControl(
            (new ButtonLink(
                t('Create Command Transport'),
                'icingadb/command-transport/add',
                'plus'
            ))->setBaseTarget('_next')
        );

        $this->addContent($list);

        $this->mergeTabs($this->Module()->getConfigTabs());
        $this->getTabs()->disableLegacyExtensions();
        $this->setTitle($this->getTabs()
            ->activate('command-transports')
            ->getActiveTab()
            ->getLabel());
    }

    public function showAction()
    {
        $transportName = $this->params->getRequired('name');

        $transportConfig = (new CommandTransportConfig())
            ->select()
            ->where('name', $transportName)
            ->fetchRow();
        if ($transportConfig === false) {
            $this->httpNotFound(t('Unknown transport'));
        }

        $form = new ApiTransportForm();
        $form->populate((array) $transportConfig);
        $form->on(ApiTransportForm::ON_SUCCESS, function (ApiTransportForm $form) use ($transportName) {
            (new CommandTransportConfig())->update(
                'transport',
                $form->getValues(),
                Filter::where('name', $transportName)
            );

            Notification::success(sprintf(t('Updated command transport "%s" successfully'), $transportName));

            $this->redirectNow('icingadb/command-transport');
        });

        $form->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($form);

        $this->addTitleTab($this->translate('Command Transport: %s'), $transportName);
        $this->getTabs()->disableLegacyExtensions();
    }

    public function addAction()
    {
        $form = new ApiTransportForm();
        $form->on(ApiTransportForm::ON_SUCCESS, function (ApiTransportForm $form) {
            (new CommandTransportConfig())->insert('transport', $form->getValues());

            Notification::success(t('Created command transport successfully'));

            $this->redirectNow('icingadb/command-transport');
        });

        $form->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($form);

        $this->addTitleTab($this->translate('Add Command Transport'));
        $this->getTabs()->disableLegacyExtensions();
    }

    public function removeAction()
    {
        $transportName = $this->params->getRequired('name');

        $form = new ConfirmRemovalForm();
        $form->setOnSuccess(function () use ($transportName) {
            (new CommandTransportConfig())->delete(
                'transport',
                Filter::where('name', $transportName)
            );

            Notification::success(sprintf(t('Removed command transport "%s" successfully'), $transportName));

            $this->redirectNow('icingadb/command-transport');
        });

        $form->handleRequest();

        $this->addContent(HtmlString::create($form->render()));

        $this->setTitle($this->translate('Remove Command Transport: %s'), $transportName);
        $this->getTabs()->disableLegacyExtensions();
    }

    public function sortAction()
    {
        $transportName = $this->params->getRequired('name');
        $newPosition = (int) $this->params->getRequired('pos');

        $config = $this->Config('commandtransports');
        if (! $config->hasSection($transportName)) {
            $this->httpNotFound(t('Unknown transport'));
        }

        if ($newPosition < 0 || $newPosition > $config->count()) {
            $this->httpBadRequest(t('Position out of bounds'));
        }

        $transports = $config->getConfigObject()->toArray();
        $transportNames = array_keys($transports);

        array_splice($transportNames, array_search($transportName, $transportNames, true), 1);
        array_splice($transportNames, $newPosition, 0, [$transportName]);

        $sortedTransports = [];
        foreach ($transportNames as $name) {
            $sortedTransports[$name] = $transports[$name];
        }

        $newConfig = Config::fromArray($sortedTransports);
        $newConfig->saveIni($config->getConfigFile());

        $this->redirectNow('icingadb/command-transport');
    }
}
