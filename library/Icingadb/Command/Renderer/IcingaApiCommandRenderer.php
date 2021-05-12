<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Renderer;

use Icinga\Module\Icingadb\Command\IcingaApiCommand;
use Icinga\Module\Icingadb\Command\Object\GetObjectCommand;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Command\Instance\ToggleInstanceFeatureCommand;
use Icinga\Module\Icingadb\Command\Object\AcknowledgeProblemCommand;
use Icinga\Module\Icingadb\Command\Object\AddCommentCommand;
use Icinga\Module\Icingadb\Command\Object\DeleteCommentCommand;
use Icinga\Module\Icingadb\Command\Object\DeleteDowntimeCommand;
use Icinga\Module\Icingadb\Command\Object\ProcessCheckResultCommand;
use Icinga\Module\Icingadb\Command\Object\PropagateHostDowntimeCommand;
use Icinga\Module\Icingadb\Command\Object\RemoveAcknowledgementCommand;
use Icinga\Module\Icingadb\Command\Object\ScheduleHostDowntimeCommand;
use Icinga\Module\Icingadb\Command\Object\ScheduleCheckCommand;
use Icinga\Module\Icingadb\Command\Object\ScheduleServiceDowntimeCommand;
use Icinga\Module\Icingadb\Command\Object\SendCustomNotificationCommand;
use Icinga\Module\Icingadb\Command\Object\ToggleObjectFeatureCommand;
use Icinga\Module\Icingadb\Command\IcingaCommand;
use InvalidArgumentException;
use ipl\Orm\Model;

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
    public function getApp()
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
    public function setApp($app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Apply filter to query data
     *
     * @param   array   $data
     * @param   Model   $object
     *
     * @return  array
     */
    protected function applyFilter(array &$data, Model $object)
    {
        if ($object instanceof Host) {
            $data['host'] = $object->name;
        } else {
            /** @var Service $object */
            $data['service'] = sprintf('%s!%s', $object->host->name, $object->name);
        }
    }

    /**
     * Render a command
     *
     * @param   IcingaCommand   $command
     *
     * @return  IcingaApiCommand
     */
    public function render(IcingaCommand $command)
    {
        $renderMethod = 'render' . $command->getName();
        if (! method_exists($this, $renderMethod)) {
            throw new InvalidArgumentException(
                sprintf('Can\'t render command. Method %s not found', $renderMethod)
            );
        }

        return $this->$renderMethod($command);
    }

    public function renderGetObject(GetObjectCommand $command)
    {
        $endpoint = sprintf(
            'objects/%s/%s',
            $command->getObjectPluralType(),
            rawurlencode($command->getObjectName())
        );

        $data = [
            'all_joins' => 1,
            'attrs'     => $command->getAttributes() ?: []
        ];

        return IcingaApiCommand::create($endpoint, $data)->setMethod('GET');
    }

    public function renderAddComment(AddCommentCommand $command)
    {
        $endpoint = 'actions/add-comment';
        $data = [
            'author'    => $command->getAuthor(),
            'comment'   => $command->getComment()
        ];

        if ($command->getExpireTime() !== null) {
            $data['expiry'] = $command->getExpireTime();
        }

        $this->applyFilter($data, $command->getObject());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderSendCustomNotification(SendCustomNotificationCommand $command)
    {
        $endpoint = 'actions/send-custom-notification';
        $data = [
            'author'    => $command->getAuthor(),
            'comment'   => $command->getComment(),
            'force'     => $command->getForced()
        ];

        $this->applyFilter($data, $command->getObject());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderProcessCheckResult(ProcessCheckResultCommand $command)
    {
        $endpoint = 'actions/process-check-result';
        $data = [
            'exit_status'       => $command->getStatus(),
            'plugin_output'     => $command->getOutput(),
            'performance_data'  => $command->getPerformanceData()
        ];

        $this->applyFilter($data, $command->getObject());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderScheduleCheck(ScheduleCheckCommand $command)
    {
        $endpoint = 'actions/reschedule-check';
        $data = [
            'next_check'    => $command->getCheckTime(),
            'force'         => $command->getForced()
        ];

        $this->applyFilter($data, $command->getObject());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderScheduleDowntime(ScheduleServiceDowntimeCommand $command)
    {
        $endpoint = 'actions/schedule-downtime';
        $data = [
            'author'        => $command->getAuthor(),
            'comment'       => $command->getComment(),
            'start_time'    => $command->getStart(),
            'end_time'      => $command->getEnd(),
            'duration'      => $command->getDuration(),
            'fixed'         => $command->getFixed(),
            'trigger_name'  => $command->getTriggerId()
        ];

        if ($command instanceof PropagateHostDowntimeCommand) {
            $data['child_options'] = $command->getTriggered() ? 1 : 2;
        }

        if ($command instanceof ScheduleHostDowntimeCommand && $command->getForAllServices()) {
            $data['all_services'] = true;
        }

        $this->applyFilter($data, $command->getObject());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderAcknowledgeProblem(AcknowledgeProblemCommand $command)
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

        $this->applyFilter($data, $command->getObject());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderToggleObjectFeature(ToggleObjectFeatureCommand $command)
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
        $object = $command->getObject();
        if ($object instanceof Host) {
            $endpoint .= 'hosts';
        } else {
            /** @var Service $object */
            $endpoint .= 'services';
        }

        $data = [
            'attrs' => [
                $attr => $command->getEnabled()
            ]
        ];

        $this->applyFilter($data, $object);
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderDeleteComment(DeleteCommentCommand $command)
    {
        $endpoint = 'actions/remove-comment';
        $data = [
            'author'    => $command->getAuthor(),
            'comment'   => $command->getCommentName()
        ];

        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderDeleteDowntime(DeleteDowntimeCommand $command)
    {
        $endpoint = 'actions/remove-downtime';
        $data = [
            'author'    => $command->getAuthor(),
            'downtime'  => $command->getDowntimeName()
        ];

        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderRemoveAcknowledgement(RemoveAcknowledgementCommand $command)
    {
        $endpoint = 'actions/remove-acknowledgement';
        $data = ['author' => $command->getAuthor()];

        $this->applyFilter($data, $command->getObject());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderToggleInstanceFeature(ToggleInstanceFeatureCommand $command)
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
