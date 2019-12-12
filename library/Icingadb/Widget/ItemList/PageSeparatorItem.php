<?php

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Widget\CommonListItem;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class PageSeparatorItem extends BaseHtmlElement
{
    protected $defaultAttributes = ['class' => 'page-separator'];

    /** @var int */
    protected $pageNumber;

    /** @var string */
    protected $tag = 'li';

    public function __construct($pageNumber)
    {
        $this->pageNumber = $pageNumber;
    }

    protected function assemble()
    {
        $this->add([Html::tag(
                'a',
                [
                    'id' => 'page-' . $this->pageNumber
                ],
                'Page ' . $this->pageNumber
            ),
            Html::tag('hr')
        ]);


    }
}
