<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Util\PluginOutput;
use ipl\Html\BaseHtmlElement;

class PluginOutputContainer extends BaseHtmlElement
{
    protected $tag = 'div';

    public function __construct(PluginOutput $output)
    {
        $this->setHtmlContent($output);

        $this->getAttributes()->registerAttributeCallback('class', function () use ($output) {
            return $output->isHtml() ? 'plugin-output' : 'plugin-output preformatted';
        });
    }
}
