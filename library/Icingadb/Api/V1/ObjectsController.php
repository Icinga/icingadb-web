<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Api\V1;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Web\Notification;
use ipl\Web\Url;

abstract class ObjectsController extends Controller
{
    use CommandActions;

    public function init()
    {
        $this->assertHttpMethod('POST');

        if ($this->isXhr()) {
            $this->httpBadRequest('This endpoint is not accessible from within Icinga Web');
        }

        if (! $this->getRequest()->isApiRequest()) {
            $this->httpBadRequest('This endpoint only responds with JSON');
        }

        parent::init();
    }

    protected function getCommandTargetsUrl(): Url
    {
        throw new NotImplementedError('The API does not issue redirects');
    }

    protected function handleCommandForm($form)
    {
        if (is_string($form)) {
            /** @var CommandForm $form */
            $form = new $form();
        }

        $form->setIsApiTarget();
        $form->setObjects($this->getCommandTargets());
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

        $this->getResponse()
            ->json()
            ->setFailData($errors)
            ->sendResponse();
    }
}
