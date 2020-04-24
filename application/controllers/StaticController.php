<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Web\Controller;

class StaticController extends Controller
{
    /**
     * Static routes don't require authentication
     *
     * @var bool
     */
    protected $requiresAuthentication = false;

    /**
     * Disable layout rendering as this controller doesn't provide any html layouts
     */
    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
    }

    public function indexAction()
    {
        $moduleRoot = $this->Module()->getBaseDir();

        $file = $this->getParam('file');
        $filePath = realpath($moduleRoot . '/public/' . rawurldecode($file));

        if ($filePath === false) {
            $this->httpNotFound(t('%s does not exist'), $filePath);
        }

        $s = stat($filePath);
        $eTag = sprintf('%x-%x-%x', $s['ino'], $s['size'], (float) str_pad($s['mtime'], 16, '0'));

        $this->getResponse()->setHeader(
            'Cache-Control',
            'public, max-age=1814400, stale-while-revalidate=604800',
            true
        );

        if ($this->getRequest()->getServer('HTTP_IF_NONE_MATCH') === $eTag) {
            $this->getResponse()
                ->setHttpResponseCode(304);
        } else {
            $this->getResponse()
                ->setHeader('ETag', $eTag)
                ->setHeader('Content-Type', mime_content_type($filePath), true)
                ->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', $s['mtime']) . ' GMT');

            readfile($filePath);
        }
    }
}
