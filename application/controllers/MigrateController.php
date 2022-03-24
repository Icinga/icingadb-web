<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Exception;
use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Exception\IcingaException;
use Icinga\Module\Icingadb\Compat\UrlMigrator;
use Icinga\Module\Icingadb\Forms\SetAsBackendForm;
use Icinga\Module\Icingadb\Hook\IcingadbSupportHook;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Web\Hook;
use ipl\Html\HtmlString;
use ipl\Web\Url;

class MigrateController extends Controller
{
    public function monitoringUrlAction()
    {
        $this->assertHttpMethod('post');
        if (! $this->getRequest()->isApiRequest()) {
            $this->httpBadRequest('No API request');
        }

        if (
            ! preg_match('/([^;]*);?/', $this->getRequest()->getHeader('Content-Type'), $matches)
            || $matches[1] !== 'application/json'
        ) {
            $this->httpBadRequest('No JSON content');
        }

        $urls = $this->getRequest()->getPost();

        $result = [];
        $errors = [];
        foreach ($urls as $urlString) {
            $url = Url::fromPath($urlString);
            if (UrlMigrator::isSupportedUrl($url)) {
                try {
                    $urlString = UrlMigrator::transformUrl($url)->getAbsoluteUrl();
                } catch (Exception $e) {
                    $errors[$urlString] = [
                        IcingaException::describe($e),
                        IcingaException::getConfidentialTraceAsString($e)
                    ];
                    $urlString = false;
                }
            }

            $result[] = $urlString;
        }

        $response = $this->getResponse()->json();
        if (empty($errors)) {
            $response->setSuccessData($result);
        } else {
            $response->setFailData([
                'result' => $result,
                'errors' => $errors
            ]);
        }

        $response->sendResponse();
    }

    public function checkboxStateAction()
    {
        $this->assertHttpMethod('get');

        $form = new SetAsBackendForm();
        $form->setAction(Url::fromPath('icingadb/migrate/checkbox-submit')->getAbsoluteUrl());

        $this->getDocument()->addHtml($form);
    }

    public function checkboxSubmitAction()
    {
        $this->assertHttpMethod('post');
        $this->addPart(HtmlString::create('"bogus"'), 'Behavior:Migrate');

        (new SetAsBackendForm())->handleRequest(ServerRequest::fromGlobals());
    }

    public function backendSupportAction()
    {
        $this->assertHttpMethod('post');
        if (! $this->getRequest()->isApiRequest()) {
            $this->httpBadRequest('No API request');
        }

        if (
            ! preg_match('/([^;]*);?/', $this->getRequest()->getHeader('Content-Type'), $matches)
            || $matches[1] !== 'application/json'
        ) {
            $this->httpBadRequest('No JSON content');
        }

        $supportList = [];
        foreach (Hook::all('Icingadb/IcingadbSupport') as $hook) {
            /** @var IcingadbSupportHook $hook */
            $supportList[$hook->getModule()->getName()] = $hook->supportsIcingaDb();
        }

        $moduleSupportStates = [];
        foreach ($this->getRequest()->getPost() as $moduleName) {
            if (isset($supportList[$moduleName])) {
                $moduleSupportStates[] = $supportList[$moduleName];
            } else {
                $moduleSupportStates[] = false;
            }
        }

        $this->getResponse()
            ->json()
            ->setSuccessData($moduleSupportStates)
            ->sendResponse();
    }
}
