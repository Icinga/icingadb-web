<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use DateInterval;
use DateTime;
use Icinga\Application\Config;
use Icinga\Web\Notification;
use ipl\Web\FormDecorator\IcingaFormDecorator;
use Iterator;
use Traversable;

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

        $allServices = $this->createElement(
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
        $this->insertBefore($allServices, $this->getElement('child_options'));
        $this->registerElement($allServices);
        $decorator->decorate($allServices);
    }

    protected function getCommands(Iterator $objects): Traversable
    {
        if (! $this->getElement('all_services')->isChecked()) {
            yield from parent::getCommands($objects);
        }

        foreach (parent::getCommands($objects) as $command) {
            yield $command->setForAllServices();
        }
    }
}
