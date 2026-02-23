<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\Link;

class TagList extends BaseHtmlElement
{
    protected $content = [];

    protected $defaultAttributes = ['class' => 'tag-list', 'data-base-target' => '_next'];

    protected $tag = 'div';

    public function addLink($content, $url): self
    {
        $this->content[] = new Link($content, $url);

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
