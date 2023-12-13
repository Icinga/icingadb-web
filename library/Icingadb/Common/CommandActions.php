<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

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
use Icinga\Security\SecurityException;
use Icinga\Web\Notification;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Web\Url;

/**
 * Trait CommandActions
 */
trait CommandActions
{
    /** @var Query $commandTargets */
    protected $commandTargets;

    /** @var Model $commandTargetModel */
    protected $commandTargetModel;

    /**
     * Get url to view command targets, used as redirection target
     *
     * @return Url
     */
    abstract protected function getCommandTargetsUrl(): Url;

    /**
     * Get status of toggleable features
     *
     * @return object
     */
    protected function getFeatureStatus()
    {
    }

    /**
     * Fetch command targets
     *
     * @return Query|Model[]
     */
    abstract protected function fetchCommandTargets();

    /**
     * Get command targets
     *
     * @return Query|Model[]
     */
    protected function getCommandTargets()
    {
        if (! isset($this->commandTargets)) {
            $this->commandTargets = $this->fetchCommandTargets();
        }

        return $this->commandTargets;
    }

    /**
     * Get the model of the command targets
     *
     * @return Model
     */
    protected function getCommandTargetModel(): Model
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
     * Check whether the permission is granted on any of the command targets
     *
     * @param string $permission
     *
     * @return bool
     */
    protected function isGrantedOnCommandTargets(string $permission): bool
    {
        $commandTargets = $this->getCommandTargets();
        if (is_array($commandTargets)) {
            foreach ($commandTargets as $commandTarget) {
                if ($this->isGrantedOn($permission, $commandTarget)) {
                    return true;
                }
            }

            return false;
        }

        return $this->isGrantedOnType(
            $permission,
            $this->getCommandTargetModel()->getTableName(),
            $commandTargets->getFilter()
        );
    }

    /**
     * Assert that the permission is granted on any of the command targets
     *
     * @param string $permission
     *
     * @throws SecurityException
     */
    protected function assertIsGrantedOnCommandTargets(string $permission)
    {
        if (! $this->isGrantedOnCommandTargets($permission)) {
            throw new SecurityException('No permission for %s', $permission);
        }
    }

    /**
     * Handle and register the given command form
     *
     * @param string|CommandForm $form
     *
     * @return void
     */
    protected function handleCommandForm($form)
    {
        $isXhr = $this->getRequest()->isXmlHttpRequest();
        $isApi = $this->getRequest()->isApiRequest();
        if ($isXhr && $isApi) {
            // Prevents the framework already, this is just a fail-safe
            $this->httpBadRequest('Responding with JSON during a Web request is not supported');
        }

        if (is_string($form)) {
            /** @var CommandForm $form */
            $form = new $form();
        }

        $form->setObjects($this->getCommandTargets());

        if (! $isApi || $isXhr) {
            $this->handleWebRequest($form);
        } else {
            $this->handleApiRequest($form);
        }
    }

    /**
     * Handle a Web request for the given form
     *
     * @param CommandForm $form
     *
     * @return void
     */
    protected function handleWebRequest(CommandForm $form): void
    {
        $actionUrl = $this->getRequest()->getUrl();
        if ($this->view->compact) {
            $actionUrl = clone $actionUrl;
            // TODO: This solves https://github.com/Icinga/icingadb-web/issues/124 but I'd like to omit this
            // entirely. I think it should be solved like https://github.com/Icinga/icingaweb2/pull/4300 so
            // that a request's url object still has params like showCompact and _dev
            $actionUrl->getParams()->add('showCompact', true);
        }

        $form->setAction($actionUrl->getAbsoluteUrl());
        $form->on($form::ON_SUCCESS, function () {
            // This forces the column to reload nearly instantly after the redirect
            // and ensures the effect of the command is visible to the user asap
            $this->getResponse()->setAutoRefreshInterval(1);

            $this->redirectNow($this->getCommandTargetsUrl());
        });

        $form->handleRequest($this->getServerRequest());

        $this->addContent($form);
    }

    /**
     * Handle an API request for the given form
     *
     * @param CommandForm $form
     *
     * @return never
     */
    protected function handleApiRequest(CommandForm $form)
    {
        $form->setIsApiTarget();
        $form->on($form::ON_SUCCESS, function () {
            $this->getResponse()
                ->json()
                ->setSuccessData(Notification::getInstance()->popMessages())
                ->sendResponse();
        });

        $form->handleRequest($this->getServerRequest());

        $errors = [];
        foreach ($form->getElements() as $element) {
            $errors[$element->getName()] = $element->getMessages();
        }

        $response = $this->getResponse()->json();
        $response->setHttpResponseCode(422);
        $response->setFailData($errors)
            ->sendResponse();
    }

    public function acknowledgeAction()
    {
        $this->assertIsGrantedOnCommandTargets('icingadb/command/acknowledge-problem');
        $this->setTitle(t('Acknowledge Problem'));
        $this->handleCommandForm(AcknowledgeProblemForm::class);
    }

    public function addCommentAction()
    {
        $this->assertIsGrantedOnCommandTargets('icingadb/command/comment/add');
        $this->setTitle(t('Add Comment'));
        $this->handleCommandForm(AddCommentForm::class);
    }

    public function checkNowAction()
    {
        if (! $this->isGrantedOnCommandTargets('icingadb/command/schedule-check/active-only')) {
            $this->assertIsGrantedOnCommandTargets('icingadb/command/schedule-check');
        }

        $this->handleCommandForm(CheckNowForm::class);
    }

    public function processCheckresultAction()
    {
        $this->assertIsGrantedOnCommandTargets('icingadb/command/process-check-result');
        $this->setTitle(t('Submit Passive Check Result'));
        $this->handleCommandForm(ProcessCheckResultForm::class);
    }

    public function removeAcknowledgementAction()
    {
        $this->assertIsGrantedOnCommandTargets('icingadb/command/remove-acknowledgement');
        $this->handleCommandForm(RemoveAcknowledgementForm::class);
    }

    public function scheduleCheckAction()
    {
        if (! $this->isGrantedOnCommandTargets('icingadb/command/schedule-check/active-only')) {
            $this->assertIsGrantedOnCommandTargets('icingadb/command/schedule-check');
        }

        $this->setTitle(t('Reschedule Check'));
        $this->handleCommandForm(ScheduleCheckForm::class);
    }

    public function scheduleDowntimeAction()
    {
        $this->assertIsGrantedOnCommandTargets('icingadb/command/downtime/schedule');

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
        $this->assertIsGrantedOnCommandTargets('icingadb/command/send-custom-notification');
        $this->setTitle(t('Send Custom Notification'));
        $this->handleCommandForm(SendCustomNotificationForm::class);
    }

    public function toggleFeaturesAction()
    {
        $commandObjects = $this->getCommandTargets();
        $form = null;
        if (count($commandObjects) > 1) {
            $this->isGrantedOnCommandTargets('i/am-only-used/to-establish/the-object-auth-cache');
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
