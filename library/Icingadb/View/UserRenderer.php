<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\View;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\User;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Common\ItemRenderer;
use ipl\Web\Widget\Ball;
use ipl\Web\Widget\Link;

/** @implements ItemRenderer<User> */
class UserRenderer implements ItemRenderer
{
    public function assembleAttributes($item, Attributes $attributes, string $layout): void
    {
        $attributes->get('class')->addValue('user');
    }

    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        $visual->addHtml(
            (new Ball($layout === 'minimal' ? Ball::SIZE_BIG : Ball::SIZE_LARGE))
                ->addAttributes(['class' => 'user-ball'])
                ->addHtml(Text::create(grapheme_substr($item->display_name, 0, 1)))
        );
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        if ($layout === 'header') {
            $title->addHtml(new HtmlElement(
                'span',
                Attributes::create(['class' => 'subject']),
                Text::create($item->display_name)
            ));
        } else {
            $title->addHtml(new Link($item->display_name, Links::user($item), ['class' => 'subject']));
        }
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        $caption->addHtml(Text::create($item->name));
    }

    public function assembleExtendedInfo($item, HtmlDocument $info, string $layout): void
    {
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
    }

    public function assemble($item, string $name, HtmlDocument $element, string $layout): bool
    {
        return false; // no custom sections
    }
}
