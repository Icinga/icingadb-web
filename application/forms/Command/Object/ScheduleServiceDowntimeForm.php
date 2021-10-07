<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use DateInterval;
use DateTime;
use Icinga\Application\Config;
use Icinga\Module\Icingadb\Command\Object\ScheduleServiceDowntimeCommand;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Web\Notification;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Orm\Model;
use ipl\Validator\CallbackValidator;
use ipl\Web\FormDecorator\IcingaFormDecorator;
use ipl\Web\Widget\Icon;

class ScheduleServiceDowntimeForm extends CommandForm
{
    use Auth;

    /** @var DateTime downtime start */
    protected $start;

    /** @var DateTime fixed downtime end */
    protected $fixedEnd;

    /**@var DateTime flexible downtime end */
    protected $flexibleEnd;

    /**  @var DateInterval flexible downtime duration */
    protected $flexibleDuration;

    /** @var mixed Comment Text */
    protected $commentText;

    /**
     * Initialize this form
     */
    public function __construct()
    {
        $this->start = new DateTime();

        $config = Config::module('icingadb');

        $this->commentText = $config->get('settings', 'hostdowntime_comment_text');
        $fixedEnd = clone $this->start;
        $fixed = $config->get('settings', 'servicedowntime_end_fixed', 'PT1H');
        $this->fixedEnd = $fixedEnd->add(new DateInterval($fixed));

        $flexibleEnd = clone $this->start;
        $flexible = $config->get('settings', 'servicedowntime_end_flexible', 'PT2H');
        $this->flexibleEnd = $flexibleEnd->add(new DateInterval($flexible));

        $flexibleDuration = $config->get('settings', 'servicedowntime_flexible_duration', 'PT2H');
        $this->flexibleDuration = new DateInterval($flexibleDuration);

        $this->on(self::ON_SUCCESS, function () {
            if ($this->errorOccurred) {
                return;
            }

            $countObjects = count($this->getObjects());

            Notification::success(sprintf(
                tp('Scheduled downtime successfully', 'Scheduled downtime for %d services successfully', $countObjects),
                $countObjects
            ));
        });
    }

    protected function assembleElements()
    {
        $isFlexible = $this->getPopulatedValue('flexible') === 'y';

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
                    Text::create(t(
                        'This command is used to schedule host and service downtimes. During the downtime specified'
                        . ' by the start and end time, Icinga will not send notifications out about the hosts and'
                        . ' services. When the scheduled downtime expires, Icinga will send out notifications for'
                        . ' the hosts and services as it normally would.'
                    ))
                )
            )
        ));

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
                ),
                'value'         => $this->commentText
            ]
        );
        $decorator->decorate($this->getElement('comment'));

        $this->addElement(
            'localDateTime',
            'start',
            [
                'data-use-datetime-picker'  => true,
                'required'                  => true,
                'value'                     => $this->start,
                'label'                     => t('Start Time'),
                'description'               => t('Set the start date and time for the downtime.')
            ]
        );
        $decorator->decorate($this->getElement('start'));

        $this->addElement(
            'localDateTime',
            'end',
            [
                'data-use-datetime-picker'  => true,
                'required'                  => true,
                'label'                     => t('End Time'),
                'description'               => t('Set the end date and time for the downtime.'),
                'value'                     => $isFlexible ? $this->flexibleEnd : $this->fixedEnd,
                'validators'                => [
                    'DateTime' => ['break_chain_on_failure' => true],
                    'Callback' => function ($value, $validator) {
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
                    }
                ]
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

        if ($isFlexible) {
            $hoursInput = $this->createElement(
                'number',
                'hours',
                [
                    'required'  => true,
                    'label'     => t('Duration'),
                    'value'     => $this->flexibleDuration->h,
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
                    'value'     => $this->flexibleDuration->m,
                    'min'       => 0
                ]
            );
            $this->registerElement($minutesInput);
            $minutesInput->addWrapper(
                new HtmlElement('label', null, new HtmlElement('span', null, Text::create(t('Minutes'))))
            );

            $hoursInput->getWrapper()
                ->add($minutesInput)
                ->getAttributes()->add('class', 'downtime-duration');
            $hoursInput->prependWrapper(
                new HtmlElement('label', null, new HtmlElement('span', null, Text::create(t('Hours'))))
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
