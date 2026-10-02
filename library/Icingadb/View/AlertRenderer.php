<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\View;

use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Model\AlertHistory;
use ipl\Html\Attributes;
use ipl\Html\FormattedString;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Web\Common\ItemRenderer;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;

/** @implements ItemRenderer<AlertHistory> */
class AlertRenderer implements ItemRenderer
{
    use Translation;

    public function assembleAttributes($item, Attributes $attributes, string $layout): void
    {
        $attributes->get('class')->addValue('alert');
    }

    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        $ballSize = StateBall::SIZE_LARGE;
        if ($layout === 'minimal' || $layout === 'header') {
            $ballSize = StateBall::SIZE_BIG;
        }

        $visual->addHtml(
            HtmlElement::create(
                'div',
                ['class' => ['icon-ball', 'ball-size-' . $ballSize]],
                new Icon(Icons::NOTIFIED)
            )
        );
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        $contact = new HtmlElement(
            'span',
            Attributes::create(['class' => 'subject']),
            Text::create($item->contact_name ?? $this->translate('unknown'))
        );

        $channel = new HtmlElement(
            'span',
            Attributes::create(['class' => 'subject']),
            Text::create($item->channel_name ?? $this->translate('unknown'))
        );

        $title->addHtml(
            FormattedString::create($this->translate('Sent notification via %s to %s'), $channel, $contact)
        );

        $membership = $this->createMembership($item);
        if ($membership !== null) {
            $title->addHtml(
                Text::create(' '),
                FormattedString::create($this->translate('(Member of %s)'), $membership)
            );
        }
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        $caption->addHtml(Text::create($item->event_message));
    }

    public function assembleExtendedInfo($item, HtmlDocument $info, string $layout): void
    {
        $info->addHtml(new TimeAgo($item->triggered_at));
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
    }

    public function assemble($item, string $name, HtmlDocument $element, string $layout): bool
    {
        return false;
    }

    /**
     * Create the contact group or schedule through which the contact was notified
     *
     * @param AlertHistory $item
     *
     * @return ?ValidHtml null if the contact was notified directly
     */
    protected function createMembership(AlertHistory $item): ?ValidHtml
    {
        if (isset($item->contactgroup_name)) {
            $icon = new Icon(Icons::USERGROUP, ['title' => $this->translate('Contact group')]);
            $name = $item->contactgroup_name;
        } elseif (isset($item->schedule_name)) {
            $icon = new Icon(Icons::SCHEDULE, ['title' => $this->translate('Schedule')]);
            $name = $item->schedule_name;
        } else {
            return null;
        }

        return new HtmlElement(
            'span',
            Attributes::create(['class' => 'membership']),
            $icon,
            Text::create($name)
        );
    }
}
