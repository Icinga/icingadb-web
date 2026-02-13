<?php

/* Icinga DB Web | (c) 2026 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Control;

use Icinga\Module\Icingadb\Common\Database;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatForm;
use ipl\Web\FormElement\TermInput;
use ipl\Web\FormElement\TermInput\Term;
use ipl\Web\Url;
use Psr\Http\Message\ServerRequestInterface;

class ColumnChooser extends CompatForm
{
    use Database;

    protected ?Url $suggestionUrl = null;
    protected $model = null;

    public function __construct(Url $suggestionUrl, $model)
    {
        $this->suggestionUrl = $suggestionUrl;
        $this->model = $model;
    }

    public function getPartUpdates(): array
    {
        $this->ensureAssembled();

        return $this->getElement('columns')->prepareMultipartUpdate($this->getRequest());
    }

    public function isValid()
    {
        if (! parent::isValid()) {
            return false;
        }

        foreach ($this->getElement('columns')->getTerms() as $term) {
            if ($term->getMessage() !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate terms, mark invalid ones, and set labels
     *
     * @param Term[] $terms
     */
    protected function validateTermsAndSetLabels(array $terms): void
    {
        $resolver = $this->model::on($this->getDb())->getResolver();

        foreach ($terms as $term) {
            try {
                $columnDefinition = $resolver->getColumnDefinition($term->getSearchValue());
                $label = $columnDefinition->getLabel();
                if ($label !== null) {
                    $term->setLabel($label);
                }
            } catch (\Exception) {
                $term->setMessage($this->translate('Is not a valid column'));
            }
        }
    }

    protected function getColumns(ServerRequestInterface $request): string
    {
        $columns = $request->getQueryParams()['columns'];

        return $columns;
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
            ->setValue($this->getColumns($this->getRequest()))
            ->on(TermInput::ON_ENRICH, $this->validateTermsAndSetLabels(...))
            ->on(TermInput::ON_ADD, $this->validateTermsAndSetLabels(...))
            ->on(TermInput::ON_SAVE, $this->validateTermsAndSetLabels(...))
            ->on(TermInput::ON_PASTE, $this->validateTermsAndSetLabels(...));

        $this->addElement($termInput);
        $this->addElement('submit', 'apply', ['label' => $this->translate('Apply')]);
    }
}
