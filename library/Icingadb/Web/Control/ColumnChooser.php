<?php

/* Icinga DB Web | (c) 2026 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Control;

use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatForm;
use ipl\Web\FormElement\TermInput;
use ipl\Web\Url;

class ColumnChooser extends CompatForm
{
    protected ?Url $suggestionUrl = null;

    public function __construct(Url $suggestionUrl)
    {
        $this->suggestionUrl = $suggestionUrl;
    }

    protected function assemble()
    {
        $this->addHtml(
            new HtmlElement(
                'div',
                new Attributes(['id' => 'column-chooser-suggestions', 'class' => 'search-suggestions'])
            )
        );
        $termInput = (new TermInput(
            'columns',
            [
                'type' => 'text',
                'label' => $this->translate('Selected Columns'),
            ]
        ))
            ->setRequired()
            ->setVerticalTermDirection()
            ->setReadOnly()
            ->setOrdered()
            ->setSuggestionUrl($this->suggestionUrl)
            ->setValue($this->getRequest()->getQueryParams()['columns']);
        $this->addElement($termInput);
        $this->addElement('submit', 'apply', ['label' => $this->translate('Apply')]);
    }
}
