<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use Icinga\Module\Icingadb\Command\Object\DeleteDowntimeCommand;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use ipl\Orm\Model;
use ipl\Web\Common\RedirectOption;
use ipl\Web\Widget\Icon;

class DeleteDowntimeForm extends CommandForm
{
    use Auth;
    use RedirectOption;

    protected $defaultAttributes = ['class' => 'inline'];

    protected function assembleElements()
    {
        $this->addElement($this->createRedirectOption());
    }

    protected function assembleSubmitButton()
    {
        $isDisabled = true;
        foreach ($this->getObjects() as $downtime) {
            if ($downtime->scheduled_by === null) {
                $isDisabled = false;
                break;
            }
        }

        $this->addElement(
            'submitButton',
            'btn_submit',
            [
                'class' => ['cancel-button', 'spinner'],
                'disabled' => $isDisabled ?: null,
                'title' => $isDisabled
                    ? t('Downtime cannot be removed at runtime because it is based on a configured scheduled downtime.')
                    : null,
                'label' => [
                    new Icon('trash'),
                    tp('Delete downtime', 'Delete downtimes', count($this->getObjects()))
                ]
            ]
        );
    }

    protected function getCommand(Model $object)
    {
        if (
            ! $this->isGrantedOn('icingadb/command/downtime/delete', $object->{$object->object_type})
            || $object->scheduled_by !== null
        ) {
            return null;
        }

        $command = new DeleteDowntimeCommand();
        $command->setDowntimeName($object->name);
        $command->setAuthor($this->getAuth()->getUser()->getUsername());

        return $command;
    }
}
