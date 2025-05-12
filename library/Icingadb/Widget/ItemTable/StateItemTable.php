<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Form;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Orm\Common\SortUtil;
use ipl\Orm\Query;
use ipl\Web\Control\SortControl;
use ipl\Web\Widget\EmptyStateBar;
use ipl\Web\Widget\Icon;

/** @todo Figure out what this might (should) have in common with the new ItemTable implementation */
abstract class StateItemTable extends BaseHtmlElement
{
    protected $baseAttributes = [
        'class' => 'state-item-table'
    ];

    /** @var array<string, string> The columns to render */
    protected $columns;

    /** @var iterable The datasource */
    protected $data;

    /** @var string The sort rules */
    protected $sort;

    protected $tag = 'table';

    /**
     * Create a new item table
     *
     * @param iterable $data Datasource of the table
     * @param array<string, string> $columns The columns to render, keys are labels
     */
    public function __construct(iterable $data, array $columns)
    {
        $this->data = $data;
        $this->columns = array_flip($columns);

        $this->addAttributes($this->baseAttributes);

        $this->init();
    }

    /**
     * Initialize the item table
     *
     * If you want to adjust the item table after construction, override this method.
     */
    protected function init()
    {
    }

    /**
     * Get the columns being rendered
     *
     * @return array<string, string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Set sort rules (as returned by {@see SortControl::getSort()})
     *
     * @param ?string $sort
     *
     * @return $this
     */
    public function setSort(?string $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    abstract protected function getItemClass(): string;

    abstract protected function getVisualColumn(): string;

    protected function getVisualLabel()
    {
        return new Icon('heartbeat', ['title' => t('Severity')]);
    }

    protected function assembleColumnHeader(BaseHtmlElement $header, string $name, $label): void
    {
        $sortRules = [];
        if ($this->sort !== null) {
            $sortRules = SortUtil::createOrderBy($this->sort);
        }

        $active = false;
        $sortDirection = null;
        foreach ($sortRules as $rule) {
            if ($rule[0] === $name) {
                $sortDirection = $rule[1];
                $active = true;
                break;
            }
        }

        if ($sortDirection === 'desc') {
            $value = "$name asc";
        } else {
            $value = "$name desc";
        }

        $icon = 'sort';
        if ($active) {
            $icon = $sortDirection === 'desc' ? 'sort-up' : 'sort-down';
        }

        $form = new Form();
        $form->setAttribute('method', 'GET');

        $button = $form->createElement('button', 'sort', [
            'value' => $value,
            'type'  => 'submit',
            'title' => is_string($label) ? $label : null,
            'class' => $active ? 'active' : null
        ]);
        $button->addHtml(
            Html::tag(
                'span',
                null,
                // With &nbsp; to have the height sized the same as the others
                $label ?? HtmlString::create('&nbsp;')
            ),
            new Icon($icon)
        );
        $form->addElement($button);

        $header->add($form);

        switch (true) {
            case substr($name, -7) === '.output':
            case substr($name, -12) === '.long_output':
                $header->getAttributes()->add('class', 'has-plugin-output');
                break;
            case substr($name, -22) === '.icon_image.icon_image':
                $header->getAttributes()->add('class', 'has-icon-images');
                break;
            case substr($name, -17) === '.performance_data':
            case substr($name, -28) === '.normalized_performance_data':
                $header->getAttributes()->add('class', 'has-performance-data');
                break;
        }
    }

    protected function assemble()
    {
        $itemClass = $this->getItemClass();

        $headerRow = new HtmlElement('tr');

        $visualCell = new HtmlElement('th', Attributes::create(['class' => 'has-visual']));
        $this->assembleColumnHeader($visualCell, $this->getVisualColumn(), $this->getVisualLabel());
        $headerRow->addHtml($visualCell);

        foreach ($this->columns as $name => $label) {
            $headerCell = new HtmlElement('th');
            $this->assembleColumnHeader($headerCell, $name, is_int($label) ? $name : $label);
            $headerRow->addHtml($headerCell);
        }

        $this->addHtml(new HtmlElement('thead', null, $headerRow));

        $body = new HtmlElement('tbody', Attributes::create(['data-base-target' => '_next']));
        foreach ($this->data as $item) {
            $body->addHtml(new $itemClass($item, $this));
        }

        if ($body->isEmpty()) {
            $body->addHtml(new HtmlElement(
                'tr',
                null,
                new HtmlElement(
                    'td',
                    Attributes::create(['colspan' => count($this->columns) + 1]),
                    new EmptyStateBar(t('No items found.'))
                )
            ));
        }

        $this->addHtml($body);
    }

    /**
     * Enrich the given list of column names with appropriate labels
     *
     * @param Query $query
     * @param array $columns
     *
     * @return array
     */
    public static function applyColumnMetaData(Query $query, array $columns): array
    {
        $newColumns = [];
        foreach ($columns as $columnPath) {
            $label = $query->getResolver()->getColumnDefinition($columnPath)->getLabel();
            $newColumns[$label ?? $columnPath] = $columnPath;
        }

        return $newColumns;
    }
}
