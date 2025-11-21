<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class PageSeparatorItem extends BaseHtmlElement
{
    protected $defaultAttributes = ['class' => 'list-item page-separator'];

    /** @var int */
    protected $pageNumber;

    /** @var string */
    protected $tag = 'li';

    public function __construct(int $pageNumber)
    {
        $this->pageNumber = $pageNumber;
    }

    protected function assemble()
    {
        $this->add(Html::tag(
            'a',
            [
                'id' => 'page-' . $this->pageNumber,
                'data-icinga-no-scroll-on-focus' => true
            ],
            $this->pageNumber
        ));
    }
}
