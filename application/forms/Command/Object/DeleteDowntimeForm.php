<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use Generator;
use Icinga\Module\Icingadb\Command\Object\DeleteDowntimeCommand;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Web\Notification;
use ipl\Web\Common\RedirectOption;
use ipl\Web\Widget\Icon;
use Traversable;

class DeleteDowntimeForm extends CommandForm
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

    protected function getCommands(Traversable $objects): Traversable
    {
        $granted = (function () use ($objects): Generator {
            foreach ($objects as $object) {
                if (
                    $this->isGrantedOn('icingadb/command/downtime/delete', $object->{$object->object_type})
                    && $object->scheduled_by === null
                ) {
                    yield $object;
                }
            }
        })();

        if ($granted->valid()) {
            $command = new DeleteDowntimeCommand();
            $command->setObjects($granted);
            $command->setAuthor($this->getAuth()->getUser()->getUsername());

            yield $command;
        }
    }
}
