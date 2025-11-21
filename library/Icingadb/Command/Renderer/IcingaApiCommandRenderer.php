<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Command\Renderer;

use Icinga\Module\Icingadb\Command\IcingaApiCommand;
use Icinga\Module\Icingadb\Command\Object\GetObjectCommand;
use Icinga\Module\Icingadb\Command\Object\ScheduleDowntimeCommand;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Command\Instance\ToggleInstanceFeatureCommand;
use Icinga\Module\Icingadb\Command\Object\AcknowledgeProblemCommand;
use Icinga\Module\Icingadb\Command\Object\AddCommentCommand;
use Icinga\Module\Icingadb\Command\Object\DeleteCommentCommand;
use Icinga\Module\Icingadb\Command\Object\DeleteDowntimeCommand;
use Icinga\Module\Icingadb\Command\Object\ProcessCheckResultCommand;
use Icinga\Module\Icingadb\Command\Object\RemoveAcknowledgementCommand;
use Icinga\Module\Icingadb\Command\Object\ScheduleCheckCommand;
use Icinga\Module\Icingadb\Command\Object\SendCustomNotificationCommand;
use Icinga\Module\Icingadb\Command\Object\ToggleObjectFeatureCommand;
use Icinga\Module\Icingadb\Command\IcingaCommand;
use InvalidArgumentException;
use ipl\Orm\Model;
use LogicException;
use Traversable;

/**
 * Icinga command renderer for the Icinga command file
 */
class IcingaApiCommandRenderer implements IcingaCommandRendererInterface
{
    /**
     * Name of the Icinga application object
     *
     * @var string
     */
    protected $app = 'app';

    /**
     * Get the name of the Icinga application object
     *
     * @return string
     */
    public function getApp(): string
    {
        return $this->app;
    }

    /**
     * Set the name of the Icinga application object
     *
     * @param   string  $app
     *
     * @return  $this
     */
    public function setApp(string $app): self
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Apply filter to query data
     *
     * @param array              $data
     * @param Traversable<Model> $objects
     *
     * @return ?Model Any of the objects (useful for further type-dependent handling)
     */
    protected function applyFilter(array &$data, Traversable $objects): ?Model
    {
        $object = null;

        foreach ($objects as $object) {
            if ($object instanceof Service) {
                $data['services'][] = sprintf('%s!%s', $object->host->name, $object->name);
            } else {
                $data['hosts'][] = $object->name;
            }
        }

        return $object;
    }

    /**
     * Get the sub-route of the endpoint for an object
     *
     * @param   Model   $object
     *
     * @return  string
     */
    protected function getObjectPluralType(Model $object): string
    {
        if ($object instanceof Host) {
            return 'hosts';
        }

        if ($object instanceof Service) {
            return 'services';
        }

        throw new LogicException(sprintf('Invalid object type %s provided', get_class($object)));
    }

    /**
     * Render a command
     *
     * @param   IcingaCommand   $command
     *
     * @return  IcingaApiCommand
     */
    public function render(IcingaCommand $command): IcingaApiCommand
    {
        $renderMethod = 'render' . $command->getName();
        if (! method_exists($this, $renderMethod)) {
            throw new InvalidArgumentException(
                sprintf('Can\'t render command. Method %s not found', $renderMethod)
            );
        }

        return $this->$renderMethod($command);
    }

    public function renderGetObject(GetObjectCommand $command): IcingaApiCommand
    {
        $data = [
            'all_joins' => 1,
            'attrs'     => $command->getAttributes()
        ];

        $endpoint = 'objects/' . $this->getObjectPluralType($this->applyFilter($data, $command->getObjects()));

        return IcingaApiCommand::create($endpoint, $data)->setMethod('GET');
    }

    public function renderAddComment(AddCommentCommand $command): IcingaApiCommand
    {
        $endpoint = 'actions/add-comment';
        $data = [
            'author'    => $command->getAuthor(),
            'comment'   => $command->getComment()
        ];

        if ($command->getExpireTime() !== null) {
            $data['expiry'] = $command->getExpireTime();
        }

        $this->applyFilter($data, $command->getObjects());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderSendCustomNotification(SendCustomNotificationCommand $command): IcingaApiCommand
    {
        $endpoint = 'actions/send-custom-notification';
        $data = [
            'author'    => $command->getAuthor(),
            'comment'   => $command->getComment(),
            'force'     => $command->getForced()
        ];

        $this->applyFilter($data, $command->getObjects());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderProcessCheckResult(ProcessCheckResultCommand $command): IcingaApiCommand
    {
        $endpoint = 'actions/process-check-result';
        $data = [
            'exit_status'       => $command->getStatus(),
            'plugin_output'     => $command->getOutput(),
            'performance_data'  => $command->getPerformanceData()
        ];

        $this->applyFilter($data, $command->getObjects());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderScheduleCheck(ScheduleCheckCommand $command): IcingaApiCommand
    {
        $endpoint = 'actions/reschedule-check';
        $data = [
            'next_check'    => $command->getCheckTime(),
            'force'         => $command->getForced()
        ];

        $this->applyFilter($data, $command->getObjects());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderScheduleDowntime(ScheduleDowntimeCommand $command): IcingaApiCommand
    {
        $endpoint = 'actions/schedule-downtime';
        $data = [
            'author'        => $command->getAuthor(),
            'comment'       => $command->getComment(),
            'start_time'    => $command->getStart(),
            'end_time'      => $command->getEnd(),
            'duration'      => $command->getDuration(),
            'fixed'         => $command->getFixed(),
            'trigger_name'  => $command->getTriggerId(),
            'child_options' => $command->getChildOption()
        ];

        if ($command->getForAllServices()) {
            $data['all_services'] = true;
        }

        $this->applyFilter($data, $command->getObjects());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderAcknowledgeProblem(AcknowledgeProblemCommand $command): IcingaApiCommand
    {
        $endpoint = 'actions/acknowledge-problem';
        $data = [
            'author'        => $command->getAuthor(),
            'comment'       => $command->getComment(),
            'sticky'        => $command->getSticky(),
            'notify'        => $command->getNotify(),
            'persistent'    => $command->getPersistent()
        ];

        if ($command->getExpireTime() !== null) {
            $data['expiry'] = $command->getExpireTime();
        }

        $this->applyFilter($data, $command->getObjects());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderToggleObjectFeature(ToggleObjectFeatureCommand $command): IcingaApiCommand
    {
        switch ($command->getFeature()) {
            case ToggleObjectFeatureCommand::FEATURE_ACTIVE_CHECKS:
                $attr = 'enable_active_checks';
                break;
            case ToggleObjectFeatureCommand::FEATURE_PASSIVE_CHECKS:
                $attr = 'enable_passive_checks';
                break;
            case ToggleObjectFeatureCommand::FEATURE_NOTIFICATIONS:
                $attr = 'enable_notifications';
                break;
            case ToggleObjectFeatureCommand::FEATURE_EVENT_HANDLER:
                $attr = 'enable_event_handler';
                break;
            case ToggleObjectFeatureCommand::FEATURE_FLAP_DETECTION:
                $attr = 'enable_flapping';
                break;
            default:
                throw new InvalidArgumentException($command->getFeature());
        }

        $endpoint = 'objects/';
        $objects = $command->getObjects();

        $data = [
            'attrs' => [
                $attr => $command->getEnabled()
            ]
        ];


        $endpoint .= $this->getObjectPluralType($this->applyFilter($data, $objects));

        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderDeleteComment(DeleteCommentCommand $command): IcingaApiCommand
    {
        $comments = [];

        foreach ($command->getObjects() as $object) {
            $comments[] = $object->name;
        }

        $endpoint = 'actions/remove-comment';
        $data = [
            'author'    => $command->getAuthor(),
            'comments'  => $comments
        ];

        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderDeleteDowntime(DeleteDowntimeCommand $command): IcingaApiCommand
    {
        $downtimes = [];

        foreach ($command->getObjects() as $object) {
            $downtimes[] = $object->name;
        }

        $endpoint = 'actions/remove-downtime';
        $data = [
            'author'    => $command->getAuthor(),
            'downtimes' => $downtimes
        ];

        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderRemoveAcknowledgement(RemoveAcknowledgementCommand $command): IcingaApiCommand
    {
        $endpoint = 'actions/remove-acknowledgement';
        $data = ['author' => $command->getAuthor()];

        $this->applyFilter($data, $command->getObjects());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderToggleInstanceFeature(ToggleInstanceFeatureCommand $command): IcingaApiCommand
    {
        $endpoint = 'objects/icingaapplications/' . $this->getApp();

        switch ($command->getFeature()) {
            case ToggleInstanceFeatureCommand::FEATURE_ACTIVE_HOST_CHECKS:
                $attr = 'enable_host_checks';
                break;
            case ToggleInstanceFeatureCommand::FEATURE_ACTIVE_SERVICE_CHECKS:
                $attr = 'enable_service_checks';
                break;
            case ToggleInstanceFeatureCommand::FEATURE_EVENT_HANDLERS:
                $attr = 'enable_event_handlers';
                break;
            case ToggleInstanceFeatureCommand::FEATURE_FLAP_DETECTION:
                $attr = 'enable_flapping';
                break;
            case ToggleInstanceFeatureCommand::FEATURE_NOTIFICATIONS:
                $attr = 'enable_notifications';
                break;
            case ToggleInstanceFeatureCommand::FEATURE_PERFORMANCE_DATA:
                $attr = 'enable_perfdata';
                break;
            default:
                throw new InvalidArgumentException($command->getFeature());
        }

        $data = [
            'attrs' => [
                $attr => $command->getEnabled()
            ]
        ];

        return IcingaApiCommand::create($endpoint, $data);
    }
}
