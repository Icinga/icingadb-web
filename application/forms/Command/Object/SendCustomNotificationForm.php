<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use Icinga\Application\Config;
use Icinga\Module\Icingadb\Command\Object\SendCustomNotificationCommand;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use ipl\Html\HtmlElement;
use ipl\Orm\Model;
use ipl\Web\FormDecorator\IcingaFormDecorator;
use ipl\Web\Widget\Icon;

class SendCustomNotificationForm extends CommandForm
{
    use Auth;

    protected function assembleElements()
    {
        $this->add(new HtmlElement('div', ['class' => 'form-description'], [
            new Icon('info-circle', ['class' => 'form-description-icon']),
            new HtmlElement('ul', null, [
                new HtmlElement('li', null, t(
                    'This command is used to send custom notifications about hosts or services.'
                ))
            ])
        ]));

        $config = Config::module('icingadb');
        $decorator = new IcingaFormDecorator();

        $this->addElement(
            'textarea',
            'comment',
            [
                'required'      => true,
                'label'         => t('Comment'),
                'description'   => t(
                    'Enter a brief description on why you\'re sending this notification. It will be sent with it.'
                )
            ]
        );
        $decorator->decorate($this->getElement('comment'));

        $this->addElement(
            'checkbox',
            'forced',
            [
                'label'         => t('Forced'),
                'value'         => (bool) $config->get('settings', 'custom_notification_forced', false),
                'description'   => t(
                    'If you check this option, the notification is sent regardless'
                    . ' of downtimes or whether notifications are enabled or not.'
                )
            ]
        );
        $decorator->decorate($this->getElement('forced'));
    }

    protected function assembleSubmitButton()
    {
        $this->addElement(
            'submit',
            'btn_submit',
            [
                'required'  => true,
                'label'     => tp('Send custom notification', 'Send custom notifications', count($this->getObjects()))
            ]
        );

        (new IcingaFormDecorator())->decorate($this->getElement('btn_submit'));
    }

    protected function getCommand(Model $object)
    {
        if (! $this->isGrantedOn('monitoring/command/send-custom-notification', $object)) {
            return null;
        }

        $command = new SendCustomNotificationCommand();
        $command->setObject($object);
        $command->setComment($this->getValue('comment'));
        $command->setForced($this->getElement('forced')->isChecked());
        $command->setAuthor($this->getAuth()->getUser()->getUsername());

        return $command;
    }
}
