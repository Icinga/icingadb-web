<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Date\DateFormatter;
use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Model\AlertHistory;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\CopyToClipboard;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;

class AlertDetail extends BaseHtmlElement
{
    use HostLink;
    use ServiceLink;
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
            Attributes::create(['class' => ['alert-message', 'collapsible'], 'data-visible-height' => 200]),
            Text::create($this->alert->event_message)
        );

        CopyToClipboard::attachTo($message);

        $eventInfo = [
            new HtmlElement('h2', null, Text::create($this->translate('Event Info'))),
            new HorizontalKeyValue(
                $this->translate('Triggered On'),
                DateFormatter::formatDateTime($this->alert->triggered_at->getTimestamp())
            ),
            new HorizontalKeyValue(
                $this->translate('Channel'),
                $this->alert->channel_name
                    ?? new EmptyState($this->translate('N/A'))
            ),
            new HorizontalKeyValue(
                $this->translate('Recipient'),
                isset($this->alert->contact_name)
                    ? [new Icon(Icons::USER), $this->alert->contact_name]
                    : new EmptyState($this->translate('N/A'))
            )
        ];

        if (isset($this->alert->contactgroup_name)) {
            $eventInfo[] = new HorizontalKeyValue(
                $this->translate('Contact Group'),
                [new Icon(Icons::USERGROUP), $this->alert->contactgroup_name]
            );
        } elseif (isset($this->alert->schedule_name)) {
            $eventInfo[] = new HorizontalKeyValue(
                $this->translate('Schedule'),
                [new Icon(Icons::SCHEDULE), $this->alert->schedule_name]
            );
        }

        $eventInfo[] = isset($this->alert->service->id)
            ? new HorizontalKeyValue(
                $this->translate('Service'),
                $this->createServiceLink($this->alert->service, $this->alert->host, true)
            )
            : new HorizontalKeyValue($this->translate('Host'), $this->createHostLink($this->alert->host, true));

        $this->addHtml(
            new HtmlElement('h2', null, Text::create($this->translate('Message'))),
            $message,
            ...$eventInfo
        );
    }
}
