<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use Icinga\Module\Icingadb\Command\Object\DeleteDowntimeCommand;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Web\Notification;
use ipl\Orm\Model;
use ipl\Web\Common\RedirectOption;
use ipl\Web\Widget\Icon;

class DeleteDowntimeForm extends CommandForm
{
    use Auth;
    use RedirectOption;

    protected $defaultAttributes = ['class' => 'inline'];

    public function __construct()
    {
        $this->on(self::ON_SUCCESS, function () {
            $countObjects = count($this->getObjects());

            Notification::success(sprintf(
                tp('Removed downtime successfully', 'Removed downtime from %d objects successfully', $countObjects),
                $countObjects
            ));
        });
    }

    protected function assembleElements()
    {
        $this->addElement($this->createRedirectOption());
    }

    protected function assembleSubmitButton()
    {
        $this->addElement(
            'submitButton',
            'btn_submit',
            [
                'class' => ['cancel-button', 'spinner'],
                'label' => [
                    new Icon('trash'),
                    tp('Delete downtime', 'Delete downtimes', count($this->getObjects()))
                ]
            ]
        );
    }

    protected function getCommand(Model $object)
    {
        if (! $this->isGrantedOn('icingadb/command/downtime/delete', $object->{$object->object_type})) {
            return null;
        }

        $command = new DeleteDowntimeCommand();
        $command->setDowntimeName($object->name);
        $command->setAuthor($this->getAuth()->getUser()->getUsername());

        return $command;
    }
}
