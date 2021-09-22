<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Web\Helper\Markdown;
use ipl\Html\BaseHtmlElement;
use ipl\Html\DeferredText;

class MarkdownLine extends BaseHtmlElement
{
    protected $tag = 'section';

    protected $defaultAttributes = ['class' => ['markdown', 'inline']];

    /**
     * MarkdownLine constructor.
     *
     * @param string $line
     */
    public function __construct(string $line)
    {
        $this->add((new DeferredText(function () use ($line) {
            return Markdown::line($line);
        }))->setEscaped(true));
    }
}
