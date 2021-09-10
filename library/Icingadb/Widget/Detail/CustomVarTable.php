<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Widget\EmptyState;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class CustomVarTable extends BaseHtmlElement
{
    protected $data;

    protected $level = 0;

    protected $tag = 'table';

    protected $defaultAttributes = [
        'data-visible-height'   => 200,
        'class'                 => 'custom-var-table name-value-table collapsible'
    ];

    public function __construct($data)
    {
        $this->data = $data;
    }

    protected function addRow($name, $value)
    {
        $this->add(Html::tag('tr', ['class' => "level-{$this->level}"], [
            Html::tag('th', $name),
            Html::tag('td', $value)
        ]));
    }

    protected function renderVar($name, $value)
    {
        $isArray = is_array($value);
        switch (true) {
            case $isArray && is_int(key($value)):
                $this->renderArray($name, $value);
                break;
            case $isArray:
                $this->renderObject($name, $value);
                break;
            default:
                $this->renderScalar($name, $value);
        }
    }

    protected function renderArray($name, array $array)
    {
        $numItems = count($array);
        $this->addRow("$name (Array)", sprintf(tp('%d item', '%d items', $numItems), $numItems));

        ++$this->level;

        ksort($array);
        foreach ($array as $key => $value) {
            $this->renderVar("[$key]", $value);
        }

        --$this->level;
    }

    protected function renderObject($name, array $object)
    {
        $numItems = count($object);
        $this->addRow($name, sprintf(tp('%d item', '%d items', $numItems), $numItems));

        ++$this->level;

        ksort($object);
        foreach ($object as $key => $value) {
            $this->renderVar($key, $value);
        }

        --$this->level;
    }

    protected function renderScalar($name, $value)
    {
        if ($value === '') {
            $value = new EmptyState(t('empty string'));
        }

        $this->addRow($name, $value);
    }

    protected function assemble()
    {
        ksort($this->data);
        foreach ($this->data as $name => $value) {
            $this->renderVar($name, $value);
        }
    }
}
