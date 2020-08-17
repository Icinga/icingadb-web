<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class HorizontalKeyValue extends BaseHtmlElement
{
    protected $key;

    protected $value;

    protected $defaultAttributes = ['class' => 'horizontal-key-value'];

    protected $tag = 'div';

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    protected function assemble()
    {
        $this->key === null ? $this->add([Html::tag('div', ['class' => 'value'], $this->value)]) : $this->add([
            Html::tag('div', ['class' => 'key'], $this->key),
            Html::tag('div', ['class' => 'value'], $this->value)
        ]);
    }
}
