<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Orm\Model;

/** @todo Figure out what this might (should) have in common with the new BaseTableRowItem implementation */
abstract class BaseStateRowItem extends BaseHtmlElement
{
    protected $defaultAttributes = ['class' => 'row-item'];

    /** @var Model */
    protected $item;

    /** @var StateItemTable */
    protected $list;

    protected $tag = 'tr';

    /**
     * Create a new row item
     *
     * @param Model $item
     * @param StateItemTable $list
     */
    public function __construct(Model $item, StateItemTable $list)
    {
        $this->item = $item;
        $this->list = $list;

        $this->init();
    }

    /**
     * Initialize the row item
     *
     * If you want to adjust the row item after construction, override this method.
     */
    protected function init()
    {
    }

    abstract protected function assembleVisual(BaseHtmlElement $visual);

    abstract protected function assembleCell(BaseHtmlElement $cell, string $path, $value);

    protected function createVisual(): BaseHtmlElement
    {
        $visual = new HtmlElement('td', Attributes::create(['class' => 'visual']));

        $this->assembleVisual($visual);

        return $visual;
    }

    protected function assemble()
    {
        $this->addHtml($this->createVisual());

        foreach ($this->list->getColumns() as $columnPath => $_) {
            $steps = explode('.', $columnPath);
            if ($steps[0] === $this->item->getTableName()) {
                array_shift($steps);
                $columnPath = implode('.', $steps);
            }

            $column = null;
            $subject = $this->item;
            foreach ($steps as $i => $step) {
                if (isset($subject->$step)) {
                    if ($subject->$step instanceof Model) {
                        $subject = $subject->$step;
                    } else {
                        $column = $step;
                    }
                } else {
                    $columnCandidate = implode('.', array_slice($steps, $i));
                    if (isset($subject->$columnCandidate)) {
                        $column = $columnCandidate;
                    } else {
                        break;
                    }
                }
            }

            $value = null;
            if ($column !== null) {
                $value = $subject->$column;
                if (is_array($value)) {
                    $value = empty($value) ? null : implode(',', $value);
                }
            }

            $cell = new HtmlElement('td');
            if ($value !== null) {
                $this->assembleCell($cell, $columnPath, $value);
            }

            $this->addHtml($cell);
        }
    }
}
