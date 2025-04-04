<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Web\Controller;

/**
 * @deprecated Will be removed with 1.3, use ContactgroupsController instead
 */
class UsergroupsController extends Controller
{
    public function preDispatch()
    {
        $url = $this->getRequest()->getUrl();
        $url->setPath(preg_replace(
            '~^icingadb/usergroups(?=/|$)~',
            'icingadb/contactgroups',
            $url->getPath()
        ));

        $this->getResponse()
            ->setHttpResponseCode(301)
            ->setHeader('Location', $url->getAbsoluteUrl())
            ->sendResponse();
    }
}
