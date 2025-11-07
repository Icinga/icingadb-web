<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use CallbackFilterIterator;
use Icinga\Module\Icingadb\Command\Object\RemoveAcknowledgementCommand;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Web\Notification;
use ipl\Orm\Model;
use ipl\Web\Widget\Icon;
use Iterator;
use Traversable;

use function ipl\Stdlib\iterable_value_first;

class RemoveAcknowledgementForm extends CommandForm
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

    protected function getCommands(Iterator $objects): Traversable
    {
        $granted = new CallbackFilterIterator($objects, function (Model $object): bool {
            return $this->isGrantedOn('icingadb/command/remove-acknowledgement', $object);
        });

        $command = new RemoveAcknowledgementCommand();
        $command->setAuthor($this->getAuth()->getUser()->getUsername());

        $granted->rewind(); // Forwards the pointer to the first element
        if ($granted->valid()) {
            // Chunk objects to avoid timeouts with large sets
            yield $command->setObjects($granted)->setChunkSize(250);
        }
    }
}
