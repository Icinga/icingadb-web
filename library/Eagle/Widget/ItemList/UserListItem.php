<?php

namespace Icinga\Module\Eagle\Widget\ItemList;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class UserListItem extends BaseHtmlElement
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
                Html::tag('div', ['class' => 'user-ball'], $this->item->display_name[0])
            ]),
            Html::tag('div', ['class' => 'title col'], [
                $this->item->name,
                Html::tag('br'),
                $this->item->display_name
            ]),
            Html::tag('div', ['class' => 'col'], $this->item->email),
            Html::tag('div', ['class' => 'col'], $this->item->pager)
        ]);
    }
}
