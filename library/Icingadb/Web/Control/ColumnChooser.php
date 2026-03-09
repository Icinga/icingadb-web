<?php

/* Icinga DB Web | (c) 2026 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Control;

use ipl\Orm\Resolver;
use ipl\Web\Compat\CompatForm;
use ipl\Web\FormElement\TermInput;
use ipl\Web\FormElement\TermInput\Term;
use ipl\Web\Url;

class ColumnChooser extends CompatForm
{
    /** @var string[] The columns already present in the url */
    protected array $columns = [];

    /** @var Url The suggestionUrl for the TermInput {@see TermInput::$suggestionUrl} */
    protected Url $suggestionUrl;

    /** @var Resolver The resolver used to validate column names and get their labels */
    protected Resolver $resolver;

    public function __construct(Url $suggestionUrl, Resolver $resolver, array $columns = [])
    {
        $this->suggestionUrl = $suggestionUrl;
        $this->resolver = $resolver;
        $this->columns = $columns;
    }

    public function getColumns(): array
    {
        return $this->columns;
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
        foreach ($terms as $term) {
            try {
                $columnDefinition = $this->resolver->getColumnDefinition($term->getSearchValue());
                $label = $columnDefinition->getLabel();
                if ($label !== null) {
                    $term->setLabel($label);
                }
            } catch (\Exception) {
                $term->setMessage($this->translate('Is not a valid column'));
            }
        }
    }

    protected function assemble()
    {
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
            ->setValue(implode(',', $this->columns))
            ->on(TermInput::ON_ENRICH, $this->validateTermsAndSetLabels(...))
            ->on(TermInput::ON_ADD, $this->validateTermsAndSetLabels(...))
            ->on(TermInput::ON_SAVE, $this->validateTermsAndSetLabels(...))
            ->on(TermInput::ON_PASTE, $this->validateTermsAndSetLabels(...));

        $this->addElement($termInput);
        $this->addElement('submit', 'apply', ['label' => $this->translate('Apply')]);
    }
}
