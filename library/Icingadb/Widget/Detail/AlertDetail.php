<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Model\AlertHistory;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\CopyToClipboard;

class AlertDetail extends BaseHtmlElement
{
    use Translation;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'alert-detail'];

    public function __construct(protected AlertHistory $alert)
    {
    }

    protected function assemble()
    {
        $message = new HtmlElement(
            'div',
            Attributes::create(['class' => 'alert-message']),
            Text::create($this->alert->event_message)
        );

        CopyToClipboard::attachTo($message);

        $this->addHtml(
            new HtmlElement('h2', null, Text::create($this->translate('Message'))),
            $message
        );
    }
}
