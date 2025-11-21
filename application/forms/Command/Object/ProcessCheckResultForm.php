<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use CallbackFilterIterator;
use Icinga\Module\Icingadb\Command\Object\ProcessCheckResultCommand;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Web\Notification;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Orm\Model;
use ipl\Web\FormDecorator\IcingaFormDecorator;
use ipl\Web\Widget\Icon;
use Iterator;
use Traversable;

use function ipl\Stdlib\iterable_value_first;

class ProcessCheckResultForm extends CommandForm
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
                    'Submitted passive check result successfully',
                    'Submitted passive check result for %d hosts successfully',
                    $countObjects
                ), $countObjects);
            } else {
                $message = sprintf(tp(
                    'Submitted passive check result successfully',
                    'Submitted passive check result for %d services successfully',
                    $countObjects
                ), $countObjects);
            }

            Notification::success($message);
        });
    }

    protected function assembleElements()
    {
        $this->addHtml(new HtmlElement(
            'div',
            Attributes::create(['class' => 'form-description']),
            new Icon('info-circle', ['class' => 'form-description-icon']),
            new HtmlElement(
                'ul',
                null,
                new HtmlElement(
                    'li',
                    null,
                    Text::create(t('This command is used to submit passive host or service check results.'))
                )
            )
        ));

        $decorator = new IcingaFormDecorator();

        /** @var Model $object */
        $object = iterable_value_first($this->getObjects());

        $this->addElement(
            'select',
            'status',
            [
                'required'      => true,
                'label'         => t('Status'),
                'description'   => t('The state this check result should report'),
                'options'       => $object instanceof Host ? [
                    ProcessCheckResultCommand::HOST_UP          => t('UP', 'icinga.state'),
                    ProcessCheckResultCommand::HOST_DOWN        => t('DOWN', 'icinga.state')
                ] : [
                    ProcessCheckResultCommand::SERVICE_OK       => t('OK', 'icinga.state'),
                    ProcessCheckResultCommand::SERVICE_WARNING  => t('WARNING', 'icinga.state'),
                    ProcessCheckResultCommand::SERVICE_CRITICAL => t('CRITICAL', 'icinga.state'),
                    ProcessCheckResultCommand::SERVICE_UNKNOWN  => t('UNKNOWN', 'icinga.state')
                ]
            ]
        );
        $decorator->decorate($this->getElement('status'));

        $this->addElement(
            'text',
            'output',
            [
                'required'      => true,
                'label'         => t('Output'),
                'description'   => t('The plugin output of this check result')
            ]
        );
        $decorator->decorate($this->getElement('output'));

        $this->addElement(
            'text',
            'perfdata',
            [
                'allowEmpty'    => true,
                'label'         => t('Performance Data'),
                'description'   => t(
                    'The performance data of this check result. Leave empty'
                    . ' if this check result has no performance data'
                )
            ]
        );
        $decorator->decorate($this->getElement('perfdata'));
    }

    protected function assembleSubmitButton()
    {
        $this->addElement(
            'submit',
            'btn_submit',
            [
                'required'  => true,
                'label'     => tp(
                    'Submit Passive Check Result',
                    'Submit Passive Check Results',
                    count($this->getObjects())
                ),
                'data-progress-label' => tp(
                    'Submitting Passive Check Result',
                    'Submitting Passive Check Results',
                    count($this->getObjects())
                )
            ]
        );

        (new IcingaFormDecorator())->decorate($this->getElement('btn_submit'));
    }

    protected function getCommands(Iterator $objects): Traversable
    {
        $granted = new CallbackFilterIterator($objects, function (Model $object): bool {
            return $object->passive_checks_enabled
                && $this->isGrantedOn('icingadb/command/process-check-result', $object);
        });

        $command = new ProcessCheckResultCommand();
        $command->setStatus($this->getValue('status'));
        $command->setOutput($this->getValue('output'));
        $command->setPerformanceData($this->getValue('perfdata'));

        $granted->rewind(); // Forwards the pointer to the first element
        if ($granted->valid()) {
            // Chunk objects to avoid timeouts with large sets
            yield $command->setObjects($granted)->setChunkSize(250);
        }
    }
}
