<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use DateInterval;
use DateTime;
use Icinga\Application\Config;
use Icinga\Module\Icingadb\Command\Object\PropagateHostDowntimeCommand;
use Icinga\Module\Icingadb\Command\Object\ScheduleHostDowntimeCommand;
use Icinga\Web\Notification;
use ipl\Orm\Model;
use ipl\Web\FormDecorator\IcingaFormDecorator;

class ScheduleHostDowntimeForm extends ScheduleServiceDowntimeForm
{
    /** @var bool */
    protected $hostDowntimeAllServices;

    public function __construct()
    {
        $this->start = new DateTime();
        $config = Config::module('icingadb');
        $this->commentText = $config->get('settings', 'hostdowntime_comment_text');

        $this->hostDowntimeAllServices = (bool) $config->get('settings', 'hostdowntime_all_services', false);

        $fixedEnd = clone $this->start;
        $fixed = $config->get('settings', 'hostdowntime_end_fixed', 'PT1H');
        $this->fixedEnd = $fixedEnd->add(new DateInterval($fixed));

        $flexibleEnd = clone $this->start;
        $flexible = $config->get('settings', 'hostdowntime_end_flexible', 'PT2H');
        $this->flexibleEnd = $flexibleEnd->add(new DateInterval($flexible));

        $flexibleDuration = $config->get('settings', 'hostdowntime_flexible_duration', 'PT2H');
        $this->flexibleDuration = new DateInterval($flexibleDuration);

        $this->on(self::ON_SUCCESS, function () {
            if ($this->errorOccurred) {
                return;
            }

            $countObjects = count($this->getObjects());

            Notification::success(sprintf(
                tp('Scheduled downtime successfully', 'Scheduled downtime for %d hosts successfully', $countObjects),
                $countObjects
            ));
        });
    }

    protected function assembleElements()
    {
        parent::assembleElements();

        $decorator = new IcingaFormDecorator();

        $this->addElement(
            'checkbox',
            'all_services',
            [
                'label'         => t('All Services'),
                'description'   => t(
                    'Sets downtime for all services for the matched host objects. If child options are set,'
                    . ' all child hosts and their services will schedule a downtime too.'
                ),
                'value'         => $this->hostDowntimeAllServices
            ]
        );
        $decorator->decorate($this->getElement('all_services'));

        $this->addElement(
            'select',
            'child_options',
            array(
                'description'   => t('Schedule child downtimes.'),
                'label'         => t('Child Options'),
                'options'       => [
                    0 => t('Do nothing with child hosts'),
                    1 => t('Schedule triggered downtime for all child hosts'),
                    2 => t('Schedule non-triggered downtime for all child hosts')
                ]
            )
        );
        $decorator->decorate($this->getElement('child_options'));
    }

    /**
     * @return ?PropagateHostDowntimeCommand|ScheduleHostDowntimeCommand
     */
    protected function getCommand(Model $object)
    {
        if (! $this->isGrantedOn('icingadb/command/downtime/schedule', $object)) {
            return null;
        }

        if (($childOptions = (int) $this->getValue('child_options'))) {
            $command = new PropagateHostDowntimeCommand();
            $command->setTriggered($childOptions === 1);
        } else {
            $command = new ScheduleHostDowntimeCommand();
        }

        $command->setObject($object);
        $command->setComment($this->getValue('comment'));
        $command->setAuthor($this->getAuth()->getUser()->getUsername());
        $command->setStart($this->getValue('start')->getTimestamp());
        $command->setEnd($this->getValue('end')->getTimestamp());
        $command->setForAllServices($this->getElement('all_services')->isChecked());

        if ($this->getElement('flexible')->isChecked()) {
            $command->setFixed(false);
            $command->setDuration(
                $this->getValue('hours') * 3600 + $this->getValue('minutes') * 60
            );
        }

        return $command;
    }
}
