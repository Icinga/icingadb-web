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

    /**
     * Shorten the given amount to 4 characters max
     *
     * @param int $amount
     *
     * @return string
     */
    protected function shortenAmount(int $amount): string
    {
        if ($amount < 10000) {
            return (string) $amount;
        }

        if ($amount < 999500) {
            return sprintf('%dk', round($amount / 1000.0));
        }

        if ($amount < 9959000) {
            return sprintf('%.1fM', $amount / 1000000.0);
        }

        // I think we can rule out amounts over 1 Billion
        return sprintf('%dM', $amount / 1000000.0);
    }

    protected function assemble()
    {
        $this->add([
            Html::tag('li', ['class' => 'object-statistics-graph'], $this->createDonut()),
            Html::tag('li', ['class' => ['object-statistics-total', 'text-center']], $this->createTotal()),
            Html::tag('li', $this->createBadges())
        ]);
    }
}
