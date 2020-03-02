<?php

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\BaseFilter;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

abstract class ObjectStatistics extends BaseHtmlElement
{
    use BaseFilter;

    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'object-statistics'];

    abstract protected function createDonut();

    abstract protected function createTotal();

    abstract protected function createBadges();

    protected function assemble()
    {
        $this->add([
            Html::tag('li', ['class' => 'object-statistics-graph'], $this->createDonut()),
            Html::tag('li', ['class' => ['object-statistics-total', 'text-center']], $this->createTotal()),
            Html::tag('li', $this->createBadges())
        ]);
    }
}
