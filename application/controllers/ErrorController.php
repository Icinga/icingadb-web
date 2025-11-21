<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Controllers\ErrorController as IcingaErrorController;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Web\Layout\Content;
use ipl\Web\Layout\Controls;
use ipl\Web\Url;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\Tabs;

class ErrorController extends IcingaErrorController
{
    /** @var HtmlDocument */
    protected $document;

    /** @var Controls */
    protected $controls;

    /** @var Content */
    protected $content;

    /** @var Tabs */
    protected $tabs;

    protected function prepareInit()
    {
        $this->document = new HtmlDocument();
        $this->document->setSeparator("\n");
        $this->controls = new Controls();
        $this->content = new Content();
        $this->tabs = new Tabs();

        $this->controls->setTabs($this->tabs);
        $this->view->document = $this->document;
    }

    public function postDispatch()
    {
        $this->tabs->add(uniqid(), [
            'active'    => true,
            'label'     => $this->view->title,
            'url'       => $this->getRequest()->getUrl()
        ]);

        if (! $this->content->isEmpty()) {
            $this->document->prepend($this->content);
        }

        if (! $this->view->compact && ! $this->controls->isEmpty()) {
            $this->document->prepend($this->controls);
        }

        parent::postDispatch();
    }

    protected function postDispatchXhr()
    {
        parent::postDispatchXhr();
        $this->getResponse()->setHeader('X-Icinga-Module', $this->getModuleName(), true);
    }

    public function errorAction()
    {
        $error = $this->getParam('error_handler');
        $exception = $error->exception;
        /** @var \Exception $exception */

        $message = $exception->getMessage();
        if (substr($message, 0, 27) !== 'Cannot load resource config') {
            $this->forward('error', 'error', 'default');
            return;
        } else {
            $this->setParam('error_handler', null);
        }

        // TODO: Find a native way for ipl-html to support enriching text with html
        $heading = Html::tag('h2', t('Database not configured'));
        $intro = Html::tag('p', ['data-base-target' => '_next'], Html::sprintf(
            'You seem to not have configured a resource for Icinga DB yet. Please %s and then tell Icinga DB Web %s.',
            new Link(
                Html::tag('strong', 'create one'),
                Url::fromPath('config/resource')
            ),
            new Link(
                Html::tag('strong', 'which one it is'),
                Url::fromPath('icingadb/config/database')
            )
        ));

        $this->content->add([$heading, $intro]);
    }
}
