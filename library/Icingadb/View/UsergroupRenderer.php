<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\View;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Usergroup;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Common\ItemRenderer;
use ipl\Web\Widget\Link;

/** @implements ItemRenderer<Usergroup> */
class UsergroupRenderer implements ItemRenderer
{
    public function assembleAttributes($item, Attributes $attributes, string $layout): void
    {
        $attributes->get('class')->addValue('user-group');
    }

    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        $visual->addHtml(new HtmlElement(
            'div',
            Attributes::create(['class' => 'usergroup-ball']),
            Text::create($item->display_name[0])
        ));
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
            $title->addHtml(new Link($item->display_name, Links::usergroup($item), ['class' => 'subject']));
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
