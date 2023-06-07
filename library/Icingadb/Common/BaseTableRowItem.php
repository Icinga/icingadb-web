<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;

abstract class BaseTableRowItem extends BaseHtmlElement
{
    protected $baseAttributes = ['class' => 'table-row'];

    /** @var object The associated list item */
    protected $item;

    /** @var ?BaseItemTable The list where the item is part of */
    protected $table;

    protected $tag = 'li';

    /**
     * Create a new table row item
     *
     * @param object       $item
     * @param BaseItemTable $table
     */
    public function __construct($item, BaseItemTable $table = null)
    {
        $this->item = $item;
        $this->table = $table;

        if ($table === null) {
            $this->setTag('div');
        }

        $this->addAttributes($this->baseAttributes);

        $this->init();
    }

    abstract protected function assembleColumns(HtmlDocument $columns);

    abstract protected function assembleTitle(BaseHtmlElement $title);

    protected function assembleVisual(BaseHtmlElement $visual)
    {
    }

    protected function createColumn($content = null): BaseHtmlElement
    {
        return new HtmlElement(
            'div',
            Attributes::create(['class' => 'col']),
            new HtmlElement(
                'div',
                Attributes::create(['class' => 'content']),
                ...Html::wantHtmlList($content)
            )
        );
    }

    protected function createColumns(): HtmlDocument
    {
        $columns = new HtmlDocument();

        $this->assembleColumns($columns);

        return $columns;
    }

    protected function createTitle(): BaseHtmlElement
    {
        $title = $this->createColumn()->addAttributes(['class' => 'title']);

        $this->assembleTitle($title->getFirst('div'));

        $title->prepend($this->createVisual());

        return $title;
    }

    /**
     * @return ?BaseHtmlElement
     */
    protected function createVisual()
    {
        $visual = new HtmlElement('div', Attributes::create(['class' => 'visual']));

        $this->assembleVisual($visual);

        return $visual->isEmpty() ? null : $visual;
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
