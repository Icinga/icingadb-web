<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Forms\Navigation;

use Icinga\Exception\ConfigurationError;
use Icinga\Forms\Navigation\NavigationItemForm;
use Icinga\Module\Icingadb\Common\Auth;

class ActionForm extends NavigationItemForm
{
    use Auth;

    /**
     * The name of the restriction to which the filter should be applied
     *
     * @var string
     */
    protected $restriction;

    public function createElements(array $formData)
    {
        parent::createElements($formData);

        $this->addElement(
            'text',
            'filter',
            array(
                'allowEmpty'    => true,
                'label'         => $this->translate('Filter'),
                'description'   => $this->translate(
                    'Display this action only for objects matching this filter. Leave it blank'
                    . ' if you want this action being displayed regardless of the object'
                )
            )
        );
    }

    public function isValid($formData): bool
    {
        if (! parent::isValid($formData)) {
            return false;
        }

        if (($filterString = $this->getValue('filter')) !== null) {
            try {
                $this->parseRestriction($filterString, $this->restriction);
            } catch (ConfigurationError $err) {
                $this->getElement('filter')->addError($err->getMessage());

                return false;
            }
        }

        return true;
    }
}
