<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use CallbackFilterIterator;
use Icinga\Module\Icingadb\Command\Object\DeleteDowntimeCommand;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Web\Notification;
use ipl\Orm\Model;
use ipl\Web\Widget\Icon;
use Iterator;
use Traversable;

class DeleteDowntimeForm extends CommandForm
{
    protected $defaultAttributes = ['class' => 'inline'];

    public function __construct()
    {
        $this->on(self::ON_SUCCESS, function () {
            if ($this->errorOccurred) {
                return;
            }

            $countObjects = count($this->getObjects());

            Notification::success(sprintf(
                tp('Removed downtime successfully', 'Removed %d downtimes successfully', $countObjects),
                $countObjects
            ));
        });
    }

    protected function assembleElements()
    {
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

    protected function getCommands(Iterator $objects): Traversable
    {
        $granted = new CallbackFilterIterator($objects, function (Model $object): bool {
            return $object->scheduled_by === null
                && $this->isGrantedOn('icingadb/command/downtime/delete', $object->{$object->object_type});
        });

        $command = new DeleteDowntimeCommand();
        $command->setAuthor($this->getAuth()->getUser()->getUsername());

        $granted->rewind(); // Forwards the pointer to the first element
        if ($granted->valid()) {
            // Chunk objects to avoid timeouts with large sets
            yield $command->setObjects($granted)->setChunkSize(250);
        }
    }
}
