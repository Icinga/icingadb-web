<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Icon;

class Notice extends BaseHtmlElement
{
    /** @var mixed */
    protected $content;

    protected $tag = 'p';

    protected $defaultAttributes = ['class' => 'notice'];

    public function __construct($content)
    {
        $this->content = $content;
    }

    protected function assemble()
    {
        $this->addHtml(new Icon('triangle-exclamation'));
        $this->addHtml((new HtmlElement('span'))->add($this->content));
        $this->addHtml(new Icon('triangle-exclamation'));
    }
}
