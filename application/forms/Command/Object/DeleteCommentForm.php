<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use CallbackFilterIterator;
use Icinga\Module\Icingadb\Command\Object\DeleteCommentCommand;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Web\Notification;
use ipl\Orm\Model;
use ipl\Web\Widget\Icon;
use Iterator;
use Traversable;

class DeleteCommentForm extends CommandForm
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
                tp('Removed comment successfully', 'Removed %d comments successfully', $countObjects),
                $countObjects
            ));
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
                'class' => ['cancel-button', 'spinner'],
                'label' => [
                    new Icon('trash'),
                    tp('Remove Comment', 'Remove Comments', count($this->getObjects()))
                ]
            ]
        );
    }

    protected function getCommands(Iterator $objects): Traversable
    {
        $granted = new CallbackFilterIterator($objects, function (Model $object): bool {
            return $this->isGrantedOn('icingadb/command/comment/delete', $object->{$object->object_type});
        });

        $command = new DeleteCommentCommand();
        $command->setAuthor($this->getAuth()->getUser()->getUsername());

        $granted->rewind(); // Forwards the pointer to the first element
        if ($granted->valid()) {
            // Chunk objects to avoid timeouts with large sets
            yield $command->setObjects($granted)->setChunkSize(500);
        }
    }
}
