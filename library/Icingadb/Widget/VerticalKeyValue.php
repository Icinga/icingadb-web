<?php

namespace Icinga\Module\Eagle\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class VerticalKeyValue extends BaseHtmlElement
{
    protected $key;

    protected $value;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'vertical-key-value'];

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    protected function assemble()
    {
        $this->add([
            Html::tag('span', ['class' => 'value'], $this->value),
            Html::tag('br'),
            Html::tag('span', ['class' => 'key'], $this->key),
        ]);
    }
}
