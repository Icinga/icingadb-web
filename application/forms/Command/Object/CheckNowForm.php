<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use CallbackFilterIterator;
use Icinga\Module\Icingadb\Command\Object\ScheduleCheckCommand;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Web\Notification;
use ipl\Orm\Model;
use ipl\Web\Widget\Icon;
use Iterator;
use LimitIterator;
use NoRewindIterator;
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

    protected function getCommands(Iterator $objects): Traversable
    {
        $granted = new CallbackFilterIterator($objects, function (Model $object): bool {
            return $this->isGrantedOn('icingadb/command/schedule-check', $object)
                || (
                    $object->active_checks_enabled
                    && $this->isGrantedOn('icingadb/command/schedule-check/active-only', $object)
                );
        });

        $command = new ScheduleCheckCommand();
        $command->setCheckTime(time());
        $command->setForced();

        $granted->rewind(); // Forwards the pointer to the first element
        while ($granted->valid()) {
            // Chunk objects to avoid timeouts with large sets
            yield $command->setObjects(new LimitIterator(new NoRewindIterator($granted), 0, 1000));
        }
    }
}
