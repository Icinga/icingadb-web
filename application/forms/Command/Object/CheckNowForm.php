<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use Icinga\Module\Icingadb\Command\Object\ScheduleCheckCommand;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Web\Notification;
use ipl\Orm\Model;
use ipl\Web\Widget\Icon;

class CheckNowForm extends CommandForm
{
    use Auth;

    protected $defaultAttributes = ['class' => 'inline'];

    public function __construct()
    {
        $this->on(self::ON_SUCCESS, function () {
            Notification::success(tp('Scheduling check..', 'Scheduling checks..', count($this->getObjects())));
        });
    }

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
                    new Icon('sync-alt'),
                    t('Check Now')
                ],
                'title' => t('Schedule the next active check to run immediately')
            ]
        );
    }

    protected function getCommand(Model $object)
    {
        if (
            ! $this->isGrantedOn('icingadb/command/schedule-check', $object)
            && (
                ! $object->active_checks_enabled
                || ! $this->isGrantedOn('icingadb/command/schedule-check/active-only', $object)
            )
        ) {
            return null;
        }

        $command = new ScheduleCheckCommand();
        $command->setObject($object);
        $command->setCheckTime(time());
        $command->setForced();

        return $command;
    }
}
