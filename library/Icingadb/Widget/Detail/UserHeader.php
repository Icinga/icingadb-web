<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Model\User;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

/**
 * @property User $object
 */
class UserHeader extends BaseObjectHeader
{
    protected $defaultAttributes = ['class' => 'user-header'];

    protected const BALL_CLASS_NAME = 'user-ball';

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(
            new HtmlElement(
                'span',
                Attributes::create(['class' => 'subject']),
                Text::create($this->object->display_name)
            ),
            new HtmlElement('span', null, Text::create($this->object->name))
        );
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
        $visual->addHtml(new HtmlElement(
            'div',
            Attributes::create(['class' => static::BALL_CLASS_NAME]),
            Text::create($this->object->display_name[0])
        ));
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml($this->createTitle());
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());
    }

    protected function assemble(): void
    {
        $this->addHtml($this->createVisual());
        $this->addHtml($this->createMain());
    }
}
