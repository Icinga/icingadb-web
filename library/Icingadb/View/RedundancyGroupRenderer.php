<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\View;

use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Widget\DependencyNodeStatistics;
use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Common\ItemRenderer;
use ipl\Web\Url;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeSince;

/** @implements ItemRenderer<RedundancyGroup> */
class RedundancyGroupRenderer implements ItemRenderer
{
    use Translation;

    public function assembleAttributes($item, Attributes $attributes, string $layout): void
    {
        $attributes->get('class')->addValue('redundancy-group');
    }

    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        $ballSize = StateBall::SIZE_LARGE;
        if ($layout === 'minimal' || $layout === 'header') {
            $ballSize = StateBall::SIZE_BIG;
        }

        $stateBall = new StateBall($item->state->getStateText(), $ballSize);
        $stateBall->add($item->state->getIcon());

        $visual->addHtml($stateBall);
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        $caption->addHtml(new DependencyNodeStatistics($item->summary));
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        if ($layout === 'header') {
            $subject = new HtmlElement(
                'span',
                Attributes::create(['class' => 'subject']),
                Text::create($item->display_name)
            );
        } else {
            $subject = new Link(
                $item->display_name,
                Url::fromPath('icingadb/redundancygroup', ['id' => bin2hex($item->id)]),
                ['class' => 'subject']
            );
        }

        if ($item->state->failed) {
            $title->addHtml(Html::sprintf(
                $this->translate('%s has no working objects', '<groupname> has ...'),
                $subject
            ));
        } else {
            $title->addHtml(Html::sprintf(
                $this->translate('%s has working objects', '<groupname> has ...'),
                $subject
            ));
        }
    }

    public function assembleExtendedInfo($item, HtmlDocument $info, string $layout): void
    {
        $info->addHtml(new TimeSince($item->state->last_state_change->getTimestamp()));
    }

    public function assemble($item, string $name, HtmlDocument $element, string $layout): bool
    {
        return $name === 'icon-image'; // Always add the icon-image section
    }
}
