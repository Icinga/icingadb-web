<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\Link;

class TagList extends BaseHtmlElement
{
    protected $content = [];

    protected $defaultAttributes = ['class' => 'tag-list'];

    protected $tag = 'div';

    public function addLink($content, $url, $attributes = null): self
    {
        $this->content[] = new Link($content, $url, $attributes);

        return $this;
    }

    public function hasContent(): bool
    {
        return ! empty($this->content);
    }

    protected function assemble()
    {
        $this->add(Html::wrapEach($this->content, 'li'));
    }
}
