<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use DateInterval;
use DateTime;
use Icinga\Module\Icingadb\Command\Object\ScheduleServiceDowntimeCommand;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use ipl\Html\HtmlElement;
use ipl\Orm\Model;
use ipl\Validator\CallbackValidator;
use ipl\Web\FormDecorator\IcingaFormDecorator;
use ipl\Web\Widget\Icon;

class ScheduleServiceDowntimeForm extends CommandForm
{
    use Auth;

    protected function assembleElements()
    {
        $this->add(new HtmlElement('div', ['class' => 'form-description'], [
            new Icon('info-circle', ['class' => 'form-description-icon']),
            new HtmlElement('ul', null, [
                new HtmlElement('li', null, t(
                    'This command is used to schedule host and service downtimes. During the downtime specified'
                    . ' by the start and end time, Icinga will not send notifications out about the hosts and'
                    . ' services. When the scheduled downtime expires, Icinga will send out notifications for'
                    . ' the hosts and services as it normally would.'
                ))
            ])
        ]));

        $decorator = new IcingaFormDecorator();

        $this->addElement(
            'textarea',
            'comment',
            [
                'required'      => true,
                'label'         => t('Comment'),
                'description'   => t(
                    'If you work with other administrators, you may find it useful to share information about'
                    . ' the host or service that is having problems. Make sure you enter a brief description of'
                    . ' what you are doing.'
                )
            ]
        );
        $decorator->decorate($this->getElement('comment'));

        $this->addElement(
            'localDateTime',
            'start',
            [
                'required'      => true,
                'value'         => new DateTime(),
                'label'         => t('Start Time'),
                'description'   => t('Set the start date and time for the downtime.')
            ]
        );
        $decorator->decorate($this->getElement('start'));

        $this->addElement(
            'localDateTime',
            'end',
            [
                'required'      => true,
                'label'         => t('End Time'),
                'description'   => t('Set the end date and time for the downtime.'),
                'value'         => (new DateTime())->add(new DateInterval('PT1H')),
                'validators'    => ['Callback' => function ($value, $validator) {
                    /** @var CallbackValidator $validator */

                    if ($value <= $this->getValue('start')) {
                        $validator->addMessage(t('The end time must be greater than the start time'));
                        return false;
                    }

                    if ($value <= (new DateTime())) {
                        $validator->addMessage(t('A downtime must not be in the past'));
                        return false;
                    }

                    return true;
                }]
            ]
        );
        $decorator->decorate($this->getElement('end'));

        $this->addElement(
            'checkbox',
            'flexible',
            [
                'class'         => 'autosubmit',
                'label'         => t('Flexible'),
                'description'   => t(
                    'To make this a flexible downtime, check this option. A flexible downtime starts when the host'
                    . ' or service enters a problem state sometime between the start and end times you specified.'
                    . ' It then lasts as long as the duration time you enter.'
                )
            ]
        );
        $decorator->decorate($this->getElement('flexible'));

        if ($this->getPopulatedValue('flexible') === 'y') {
            $hoursInput = $this->createElement(
                'number',
                'hours',
                [
                    'required'  => true,
                    'label'     => t('Duration'),
                    'value'     => 2,
                    'min'       => 0
                ]
            );
            $this->registerElement($hoursInput);
            $decorator->decorate($hoursInput);

            $minutesInput = $this->createElement(
                'number',
                'minutes',
                [
                    'required'  => true,
                    'value'     => 0,
                    'min'       => 0
                ]
            );
            $this->registerElement($minutesInput);
            $minutesInput->addWrapper(
                new HtmlElement('label', null, new HtmlElement('span', null, t('Minutes')))
            );

            $hoursInput->getWrapper()
                ->add($minutesInput)
                ->getAttributes()->add('class', 'downtime-duration');
            $hoursInput->prependWrapper(
                new HtmlElement('label', null, new HtmlElement('span', null, t('Hours')))
            );

            $this->add($hoursInput);
        }
    }

    protected function assembleSubmitButton()
    {
        $this->addElement(
            'submit',
            'btn_submit',
            [
                'required'  => true,
                'label'     => tp('Schedule downtime', 'Schedule downtimes', count($this->getObjects()))
            ]
        );

        (new IcingaFormDecorator())->decorate($this->getElement('btn_submit'));
    }

    protected function getCommand(Model $object)
    {
        if (! $this->isGrantedOn('icingadb/command/downtime/schedule', $object)) {
            return null;
        }

        $command = new ScheduleServiceDowntimeCommand();
        $command->setObject($object);
        $command->setComment($this->getValue('comment'));
        $command->setAuthor($this->getAuth()->getUser()->getUsername());
        $command->setStart($this->getValue('start')->getTimestamp());
        $command->setEnd($this->getValue('end')->getTimestamp());

        if ($this->getElement('flexible')->isChecked()) {
            $command->setFixed(false);
            $command->setDuration(
                $this->getValue('hours') * 3600 + $this->getValue('minutes') * 60
            );
        }

        return $command;
    }
}
