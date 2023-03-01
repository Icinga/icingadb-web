<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use Icinga\Module\Icingadb\Command\Object\ScheduleCheckCommand;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Web\Notification;
use ipl\Web\Widget\Icon;
use Traversable;

class CheckNowForm extends CommandForm
{
    protected $defaultAttributes = ['class' => 'inline'];

    public function __construct()
    {
        $this->on(self::ON_SUCCESS, function () {
            if (! $this->errorOccurred) {
                Notification::success(tp('Scheduling check..', 'Scheduling checks..', count($this->getObjects())));
            }
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

    protected function getCommands(Traversable $objects): Traversable
    {
        foreach ($objects as $object) {
            if (
                ! $this->isGrantedOn('icingadb/command/schedule-check', $object)
                && (
                    ! $object->active_checks_enabled
                    || ! $this->isGrantedOn('icingadb/command/schedule-check/active-only', $object)
                )
            ) {
                continue;
            }

            $command = new ScheduleCheckCommand();
            $command->setObjects([$object]);
            $command->setCheckTime(time());
            $command->setForced();

            yield $command;
        }
    }
}
