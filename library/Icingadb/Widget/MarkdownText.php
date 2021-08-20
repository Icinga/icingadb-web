<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Web\Helper\Markdown;
use ipl\Html\BaseHtmlElement;
use ipl\Html\DeferredText;

class MarkdownText extends BaseHtmlElement
{
    protected $tag = 'section';

    protected $defaultAttributes = ['class' => 'markdown'];

    /**
     * MarkdownText constructor.
     *
     * @param string $text
     */
    public function __construct($text)
    {
        $this->add((new DeferredText(function () use ($text) {
            return Markdown::text($text);
        }))->setEscaped(true));
    }
}
