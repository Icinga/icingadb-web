<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Exception;
use Icinga\Exception\IcingaException;
use Icinga\Module\Icingadb\Compat\UrlMigrator;
use Icinga\Module\Icingadb\Icingadb;
use Icinga\Module\Icingadb\Web\Controller;
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

        $this->getResponse()
            ->setBody(Icingadb::isSetAsBackend())
            ->sendResponse();
        exit;
    }
}
