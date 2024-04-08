<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use CallbackFilterIterator;
use Icinga\Application\Config;
use Icinga\Module\Icingadb\Command\Object\SendCustomNotificationCommand;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Web\Notification;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Orm\Model;
use ipl\Web\FormDecorator\IcingaFormDecorator;
use ipl\Web\Widget\Icon;
use Iterator;
use Traversable;

use function ipl\Stdlib\iterable_value_first;

class SendCustomNotificationForm extends CommandForm
{
    public function __construct()
    {
        $this->on(self::ON_SUCCESS, function () {
            if ($this->errorOccurred) {
                return;
            }

            $countObjects = count($this->getObjects());
            if (iterable_value_first($this->getObjects()) instanceof Host) {
                $message = sprintf(tp(
                    'Sent custom notification successfully',
                    'Sent custom notification for %d hosts successfully',
                    $countObjects
                ), $countObjects);
            } else {
                $message = sprintf(tp(
                    'Sent custom notification successfully',
                    'Sent custom notification for %d services successfully',
                    $countObjects
                ), $countObjects);
            }

            Notification::success($message);
        });
    }

    protected function assembleElements()
    {
        $this->addHtml(new HtmlElement(
            'div',
            Attributes::create(['class' => 'form-description']),
            new Icon('info-circle', ['class' => 'form-description-icon']),
            new HtmlElement(
                'ul',
                null,
                new HtmlElement(
                    'li',
                    null,
                    Text::create(t('This command is used to send custom notifications about hosts or services.'))
                )
            )
        ));

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

    protected function getCommands(Iterator $objects): Traversable
    {
        $granted = new CallbackFilterIterator($objects, function (Model $object): bool {
            return $this->isGrantedOn('icingadb/command/send-custom-notification', $object);
        });

        $granted->rewind(); // Forwards the pointer to the first element
        if ($granted->valid()) {
            $command = new SendCustomNotificationCommand();
            $command->setObjects($granted);
            $command->setComment($this->getValue('comment'));
            $command->setForced($this->getElement('forced')->isChecked());
            $command->setAuthor($this->getAuth()->getUser()->getUsername());

            yield $command;
        }
    }
}
