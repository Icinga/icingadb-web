<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use Icinga\Module\Icingadb\Command\Object\DeleteCommentCommand;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Web\Notification;
use ipl\Web\Common\RedirectOption;
use ipl\Web\Widget\Icon;
use Traversable;

class DeleteCommentForm extends CommandForm
{
    use RedirectOption;

    protected $defaultAttributes = ['class' => 'inline'];

    public function __construct()
    {
        $this->on(self::ON_SUCCESS, function () {
            if ($this->errorOccurred) {
                return;
            }

            $countObjects = count($this->getObjects());

            Notification::success(sprintf(
                tp('Removed comment successfully', 'Removed comment from %d objects successfully', $countObjects),
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
                    tp('Remove Comment', 'Remove Comments', count($this->getObjects()))
                ]
            ]
        );
    }

    protected function getCommands(Traversable $objects): Traversable
    {
        foreach ($objects as $object) {
            if (! $this->isGrantedOn('icingadb/command/comment/delete', $object->{$object->object_type})) {
                continue;
            }

            $command = new DeleteCommentCommand();
            $command->setCommentName($object->name);
            $command->setAuthor($this->getAuth()->getUser()->getUsername());

            yield $command;
        }
    }
}
