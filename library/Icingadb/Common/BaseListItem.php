<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Module\Icingadb\Common\BaseItemList;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Filter\Rule;
use ipl\Web\Filter\QueryString;

/**
 * Base class for list items
 */
abstract class BaseListItem extends BaseHtmlElement
{
    protected $baseAttributes = ['class' => 'list-item'];

    /** @var object The associated list item */
    protected $item;

    /** @var BaseItemList The list where the item is part of */
    protected $list;

    protected $tag = 'li';

    /**
     * Create a new list item
     *
     * @param object       $item
     * @param BaseItemList $list
     */
    public function __construct($item, BaseItemList $list)
    {
        $this->item = $item;
        $this->list = $list;

        $this->addAttributes($this->baseAttributes);

        $this->init();
    }

    abstract protected function assembleHeader(BaseHtmlElement $header);

    abstract protected function assembleMain(BaseHtmlElement $main);

    protected function assembleFooter(BaseHtmlElement $footer)
    {
    }

    protected function assembleCaption(BaseHtmlElement $caption)
    {
    }

    protected function assembleIconImage(BaseHtmlElement $iconImage)
    {
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
    }

    protected function assembleVisual(BaseHtmlElement $visual)
    {
    }

    protected function createCaption()
    {
        $caption = Html::tag('section', ['class' => 'caption']);

        $this->assembleCaption($caption);

        return $caption;
    }

    protected function createHeader()
    {
        $header = Html::tag('header');

        $this->assembleHeader($header);

        return $header;
    }

    protected function createMain()
    {
        $main = Html::tag('div', ['class' => 'main']);

        $this->assembleMain($main);

        return $main;
    }

    protected function createFooter()
    {
        $footer = new HtmlElement('footer');

        $this->assembleFooter($footer);

        return $footer;
    }

    protected function createIconImage()
    {
        if (! $this->list->hasIconImages()) {
            return null;
        }

        $iconImage = HtmlElement::create('div', [
            'class' => 'icon-image',
        ]);

        $this->assembleIconImage($iconImage);

        return $iconImage;
    }

    protected function createTimestamp()
    {
    }

    protected function createTitle()
    {
        $title = HTML::tag('div', ['class' => 'title']);

        $this->assembleTitle($title);

        return $title;
    }

    protected function createVisual()
    {
        $visual = Html::tag('div', ['class' => 'visual']);

        $this->assembleVisual($visual);

        return $visual;
    }

    /**
     * Initialize the list item
     *
     * If you want to adjust the list item after construction, override this method.
     */
    protected function init()
    {
    }

    protected function setMultiselectFilter(Rule $filter)
    {
        $this->addAttributes(['data-icinga-multiselect-filter' => '(' . QueryString::render($filter) . ')']);

        return $this;
    }

    protected function setDetailFilter(Rule $filter)
    {
        $this->addAttributes(['data-icinga-detail-filter' => QueryString::render($filter)]);

        return $this;
    }

    protected function assemble()
    {
        $this->add([
            $this->createVisual(),
            $this->createIconImage(),
            $this->createMain()
        ]);
    }
}
