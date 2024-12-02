<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Orm\Model;

abstract class BaseObjectHeader extends BaseHtmlElement
{
    /** @var array<string, mixed> */
    protected $baseAttributes = ['class' => 'object-header'];

    /** @var Model The associated object */
    protected $object;

    protected $tag = 'div';

    /**
     * Create a new object header
     *
     * @param Model $object
     */
    public function __construct(Model $object)
    {
        $this->object = $object;

        $this->addAttributes($this->baseAttributes);

        $this->init();
    }

    abstract protected function assembleHeader(BaseHtmlElement $header): void;

    abstract protected function assembleMain(BaseHtmlElement $main): void;

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
    }

    protected function assembleTitle(BaseHtmlElement $title): void
    {
    }

    protected function assembleVisual(BaseHtmlElement $visual): void
    {
    }

    protected function createCaption(): BaseHtmlElement
    {
        $caption = new HtmlElement('section', Attributes::create(['class' => 'caption']));

        $this->assembleCaption($caption);

        return $caption;
    }

    protected function createHeader(): BaseHtmlElement
    {
        $header = new HtmlElement('header');

        $this->assembleHeader($header);

        return $header;
    }

    protected function createMain(): BaseHtmlElement
    {
        $main = new HtmlElement('div', Attributes::create(['class' => 'main']));

        $this->assembleMain($main);

        return $main;
    }

    protected function createTimestamp(): ?BaseHtmlElement
    {
        return null;
    }

    protected function createTitle(): BaseHtmlElement
    {
        $title = new HtmlElement('div', Attributes::create(['class' => 'title']));

        $this->assembleTitle($title);

        return $title;
    }

    /**
     * @return ?BaseHtmlElement
     */
    protected function createVisual(): ?BaseHtmlElement
    {
        $visual = new HtmlElement('div', Attributes::create(['class' => 'visual']));

        $this->assembleVisual($visual);
        if ($visual->isEmpty()) {
            return null;
        }

        return $visual;
    }

    /**
     * Initialize the list item
     *
     * If you want to adjust the object header after construction, override this method.
     */
    protected function init(): void
    {
    }

    protected function assemble(): void
    {
        $this->add([
            $this->createVisual(),
            $this->createMain()
        ]);
    }
}
