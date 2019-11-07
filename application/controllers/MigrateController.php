<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Compat\UrlMigrator;
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

        // TODO: Also verify content-type!

        $urls = $this->getRequest()->getPost();

        $result = [];
        foreach ($urls as $urlString) {
            $url = Url::fromPath($urlString);
            if (UrlMigrator::isSupportedUrl($url)) {
                $urlString = UrlMigrator::transformUrl($url)->getAbsoluteUrl();
            }

            $result[] = $urlString;
        }

        $this->getResponse()->json()
            ->setSuccessData($result)
            ->sendResponse();
    }
}
