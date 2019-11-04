<?php

namespace Icinga\Module\Eagle\Widget\ItemList;

use Icinga\Module\Eagle\Common\Links;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\Link;

class UsergroupListItem extends BaseHtmlElement
{
    protected $item;

    protected $defaultAttributes = ['class' => 'list-item'];

    protected $tag = 'li';

    public function __construct($item)
    {
        $this->item = $item;
    }

    protected function assemble()
    {
        $this->add([
            Html::tag('div', ['class' => 'visual'], [
                Html::tag('div', ['class' => 'usergroup-ball'], $this->item->display_name[0])
            ]),
            Html::tag('div', ['class' => 'title col'], [
                new Link($this->item->display_name, Links::usergroup($this->item)),
                Html::tag('br'),
                $this->item->name
            ])
        ]);
    }
}
