<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\BaseFilter;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\ValidHtml;

abstract class ObjectStatistics extends BaseHtmlElement
{
    use BaseFilter;

    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'object-statistics'];

    abstract protected function createDonut(): ValidHtml;

    abstract protected function createTotal(): ValidHtml;

    abstract protected function createBadges(): ValidHtml;

    protected function assemble()
    {
        $this->add([
            Html::tag('li', ['class' => 'object-statistics-graph'], $this->createDonut()),
            Html::tag('li', ['class' => ['object-statistics-total', 'text-center']], $this->createTotal()),
            Html::tag('li', $this->createBadges())
        ]);
    }
}
