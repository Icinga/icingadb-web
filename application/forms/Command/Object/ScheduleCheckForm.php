<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use DateInterval;
use DateTime;
use Icinga\Module\Icingadb\Command\Object\ScheduleCheckCommand;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Web\Notification;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Orm\Model;
use ipl\Web\FormDecorator\IcingaFormDecorator;
use ipl\Web\Widget\Icon;

class ScheduleCheckForm extends CommandForm
{
    use Auth;

    public function __construct()
    {
        $this->on(self::ON_SUCCESS, function () {
            $countObjects = count($this->getObjects());
            if (current($this->getObjects()) instanceof Host) {
                $message = sprintf(
                    tp('Scheduled check successfully', 'Scheduled check for %d hosts successfully', $countObjects),
                    $countObjects
                );
            } else {
                $message = sprintf(
                    tp('Scheduled check successfully', 'Scheduled check for %d services successfully', $countObjects),
                    $countObjects
                );
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
                    Text::create(t(
                        'This command is used to schedule the next check of hosts or services. Icinga'
                        . ' will re-queue the hosts or services to be checked at the time you specify.'
                    ))
                )
            )
        ));

        $decorator = new IcingaFormDecorator();

        $this->addElement(
            'localDateTime',
            'check_time',
            [
                'data-use-datetime-picker'  => true,
                'required'                  => true,
                'label'                     => t('Check Time'),
                'description'               => t('Set the date and time when the check should be scheduled.'),
                'value'                     => (new DateTime())->add(new DateInterval('PT1H'))
            ]
        );
        $decorator->decorate($this->getElement('check_time'));

        $this->addElement(
            'checkbox',
            'force_check',
            [
                'label'         => t('Force Check'),
                'description'   => t(
                    'If you select this option, Icinga will force a check regardless of both what time the'
                    . ' scheduled check occurs and whether or not checks are enabled.'
                )
            ]
        );
        $decorator->decorate($this->getElement('force_check'));
    }

    protected function assembleSubmitButton()
    {
        $this->addElement(
            'submit',
            'btn_submit',
            [
                'required'  => true,
                'label'     => tp('Schedule check', 'Schedule checks', count($this->getObjects()))
            ]
        );

        (new IcingaFormDecorator())->decorate($this->getElement('btn_submit'));
    }

    protected function getCommand(Model $object)
    {
        if (
            ! $this->isGrantedOn('icingadb/command/schedule-check', $object)
            && (
                ! $object->active_checks_enabled
                || ! $this->isGrantedOn('icingadb/command/schedule-check/active-only', $object)
            )
        ) {
            return null;
        }

        $command = new ScheduleCheckCommand();
        $command->setObject($object);
        $command->setForced($this->getElement('force_check')->isChecked());
        $command->setCheckTime($this->getValue('check_time')->getTimestamp());

        return $command;
    }
}
