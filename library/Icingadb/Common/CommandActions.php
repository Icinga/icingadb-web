<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Module\Icingadb\Forms\Command\Object\AcknowledgeProblemForm;
use Icinga\Module\Icingadb\Forms\Command\Object\AddCommentForm;
use Icinga\Module\Icingadb\Forms\Command\Object\CheckNowForm;
use Icinga\Module\Icingadb\Forms\Command\Object\ProcessCheckResultForm;
use Icinga\Module\Icingadb\Forms\Command\Object\RemoveAcknowledgementForm;
use Icinga\Module\Icingadb\Forms\Command\Object\ScheduleCheckForm;
use Icinga\Module\Icingadb\Forms\Command\Object\ScheduleHostDowntimeForm;
use Icinga\Module\Icingadb\Forms\Command\Object\ScheduleServiceDowntimeForm;
use Icinga\Module\Icingadb\Forms\Command\Object\SendCustomNotificationForm;
use Icinga\Module\Icingadb\Forms\Command\Object\ToggleObjectFeaturesForm;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Web\Url;
use LogicException;

/**
 * Trait CommandActions
 *
 * @method mixed fetchCommandTargets() Fetch command targets, \ipl\Orm\Query or \ipl\Orm\Model[]
 * @method object getFeatureStatus() Get status of toggleable features
 * @method Url getCommandTargetsUrl() Get url to view command targets, used as redirection target
 */
trait CommandActions
{
    /** @var Query $commandTargets */
    protected $commandTargets;

    /** @var Model $commandTargetModel */
    protected $commandTargetModel;

    /**
     * Get command targets
     *
     * @return Query|Model[]
     */
    protected function getCommandTargets()
    {
        if (! isset($this->commandTargets)) {
            if (method_exists($this, 'fetchCommandTargets')) {
                $this->commandTargets = $this->fetchCommandTargets();
            } else {
                throw new LogicException('You must implement fetchCommandTargets() first');
            }
        }

        return $this->commandTargets;
    }

    /**
     * Get the model of the command targets
     *
     * @return Model
     */
    protected function getCommandTargetModel()
    {
        if (! isset($this->commandTargetModel)) {
            $commandTargets = $this->getCommandTargets();
            if (is_array($commandTargets) && !empty($commandTargets)) {
                $this->commandTargetModel = $commandTargets[0];
            } else {
                $this->commandTargetModel = $commandTargets->getModel();
            }
        }

        return $this->commandTargetModel;
    }

    /**
     * Handle and register the given command form
     *
     * @param string|CommandForm $form
     */
    protected function handleCommandForm($form)
    {
        if (is_string($form)) {
            /** @var \Icinga\Module\Icingadb\Forms\Command\CommandForm $form */
            $form = new $form();
        }

        $actionUrl = $this->getRequest()->getUrl();
        if ($this->view->compact) {
            $actionUrl = clone $actionUrl;
            // TODO: This solves https://github.com/Icinga/icingadb-web/issues/124 but I'd like to omit this
            // entirely. I think it should be solved like https://github.com/Icinga/icingaweb2/pull/4300 so
            // that a request's url object still has params like showCompact and _dev
            $actionUrl->getParams()->add('showCompact', true);
        }

        $form->setAction($actionUrl->getAbsoluteUrl());
        $form->setObjects($this->getCommandTargets());
        $form->on($form::ON_SUCCESS, function () {
            // This forces the column to reload nearly instantly after the redirect
            // and ensures the effect of the command is visible to the user asap
            $this->getResponse()->setAutoRefreshInterval(1);

            $this->redirectNow($this->getCommandTargetsUrl());
        });

        $form->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($form);
    }

    public function acknowledgeAction()
    {
        $this->assertPermission('monitoring/command/acknowledge-problem');
        $this->setTitle(t('Acknowledge Problem'));
        $this->handleCommandForm(AcknowledgeProblemForm::class);
    }

    public function addCommentAction()
    {
        $this->assertPermission('monitoring/command/comment/add');
        $this->setTitle(t('Add Comment'));
        $this->handleCommandForm(AddCommentForm::class);
    }

    public function checkNowAction()
    {
        $this->assertPermission('monitoring/command/schedule-check');
        $this->handleCommandForm(CheckNowForm::class);
    }

    public function processCheckresultAction()
    {
        $this->assertPermission('monitoring/command/process-check-result');
        $this->setTitle(t('Submit Passive Check Result'));
        $this->handleCommandForm(ProcessCheckResultForm::class);
    }

    public function removeAcknowledgementAction()
    {
        $this->assertPermission('monitoring/command/remove-acknowledgement');
        $this->handleCommandForm(RemoveAcknowledgementForm::class);
    }

    public function scheduleCheckAction()
    {
        $this->assertPermission('monitoring/command/schedule-check');
        $this->setTitle(t('Reschedule Check'));
        $this->handleCommandForm(ScheduleCheckForm::class);
    }

    public function scheduleDowntimeAction()
    {
        $this->assertPermission('monitoring/command/downtime/schedule');

        switch ($this->getCommandTargetModel()->getTableName()) {
            case 'host':
                $this->setTitle(t('Schedule Host Downtime'));
                $this->handleCommandForm(ScheduleHostDowntimeForm::class);
                break;
            case 'service':
                $this->setTitle(t('Schedule Service Downtime'));
                $this->handleCommandForm(ScheduleServiceDowntimeForm::class);
                break;
        }
    }

    public function sendCustomNotificationAction()
    {
        $this->assertPermission('monitoring/command/send-custom-notification');
        $this->setTitle(t('Send Custom Notification'));
        $this->handleCommandForm(SendCustomNotificationForm::class);
    }

    public function toggleFeaturesAction()
    {
        $commandObjects = $this->getCommandTargets();
        if (count($commandObjects) > 1) {
            if (! method_exists($this, 'getFeatureStatus')) {
                throw new LogicException('You must implement getFeatureStatus() first');
            }

            $form = new ToggleObjectFeaturesForm($this->getFeatureStatus());
        } else {
            foreach ($commandObjects as $object) {
                // There's only a single result, a foreach is the most compatible way to retrieve the object
                $form = new ToggleObjectFeaturesForm($object);
            }
        }

        $this->handleCommandForm($form);
    }
}
