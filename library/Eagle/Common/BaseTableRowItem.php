<?php

namespace Icinga\Module\Eagle\Common;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

abstract class BaseTableRowItem extends BaseHtmlElement
{
    protected $baseAttributes = ['class' => 'list-item'];

    /** @var object The associated list item */
    protected $item;

    protected $tag = 'li';

    /**
     * Create a new table row item
     *
     * @param object $item
     */
    public function __construct($item)
    {
        $this->item = $item;

        $this->addAttributes($this->baseAttributes);

        $this->init();
    }

    abstract protected function assembleColumns(HtmlDocument $columns);

    abstract protected function assembleTitle(BaseHtmlElement $title);

    protected function createColumn($content = null)
    {
        return Html::tag('div', ['class' => 'col'], $content);
    }

    protected function createColumns()
    {
        $columns = new HtmlDocument();

        $this->assembleColumns($columns);

        return $columns;
    }

    protected function createTitle()
    {
        $title = $this->createColumn()->addAttributes(['class' => 'title']);

        $this->assembleTitle($title);

        return $title;
    }

    /**
     * Initialize the list item
     *
     * If you want to adjust the list item after construction, override this method.
     */
    protected function init()
    {
    }

    protected function assemble()
    {
        $this->add([
            $this->createTitle(),
            $this->createColumns()
        ]);
    }
}
