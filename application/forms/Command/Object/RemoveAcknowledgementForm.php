<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use Icinga\Module\Icingadb\Command\Object\RemoveAcknowledgementCommand;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Web\Notification;
use ipl\Web\Widget\Icon;
use Traversable;

class RemoveAcknowledgementForm extends CommandForm
{
    public function __construct()
    {
        $this->on(self::ON_SUCCESS, function () {
            if ($this->errorOccurred) {
                return;
            }

            $countObjects = count($this->getObjects());
            if (current($this->getObjects()) instanceof Host) {
                $message = sprintf(tp(
                    'Removed acknowledgment successfully',
                    'Removed acknowledgment from %d hosts successfully',
                    $countObjects
                ), $countObjects);
            } else {
                $message = sprintf(tp(
                    'Removed acknowledgment successfully',
                    'Removed acknowledgment from %d services successfully',
                    $countObjects
                ), $countObjects);
            }

            Notification::success($message);
        });
    }

    protected $defaultAttributes = ['class' => 'inline'];

    protected function assembleElements()
    {
    }

    protected function assembleSubmitButton()
    {
        $this->addElement(
            'submitButton',
            'btn_submit',
            [
                'class' => ['link-button', 'spinner'],
                'label' => [
                    new Icon('trash'),
                    tp('Remove acknowledgement', 'Remove acknowledgements', count($this->getObjects()))
                ]
            ]
        );
    }

    protected function getCommands(Traversable $objects): Traversable
    {
        $granted = $this->filterGrantedOn('icingadb/command/remove-acknowledgement', $objects);

        if ($granted->valid()) {
            $command = new RemoveAcknowledgementCommand();
            $command->setObjects($granted);
            $command->setAuthor($this->getAuth()->getUser()->getUsername());

            yield $command;
        }
    }
}
