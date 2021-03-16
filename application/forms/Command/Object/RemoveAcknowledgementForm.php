<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use Icinga\Module\Icingadb\Command\Object\RemoveAcknowledgementCommand;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use ipl\Orm\Model;
use ipl\Web\Widget\Icon;

class RemoveAcknowledgementForm extends CommandForm
{
    use Auth;

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

    protected function getCommand(Model $object)
    {
        if (! $this->isGrantedOn('icingadb/command/remove-acknowledgement', $object)) {
            return null;
        }

        $command = new RemoveAcknowledgementCommand();
        $command->setObject($object);
        $command->setAuthor($this->getAuth()->getUser()->getUsername());

        return $command;
    }
}
