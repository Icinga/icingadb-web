<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Setup;

use Icinga\Forms\Config\ResourceConfigForm;
use Icinga\Forms\Config\Resource\DbResourceForm;
use Icinga\Web\Form;

class DbResourcePage extends Form
{
    public function init()
    {
        $this->setName('setup_icingadb_resource');
        $this->setTitle(t('Icinga DB Resource'));
        $this->addDescription(t(
            'Please fill out the connection details below to access Icinga DB.'
        ));
        $this->setValidatePartial(true);
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'hidden',
            'type',
            [
                'required'  => true,
                'disabled'  => true,
                'value'     => 'db'
            ]
        );

        if (isset($formData['skip_validation']) && $formData['skip_validation']) {
            // In case another error occured and the checkbox was displayed before
            $this->addSkipValidationCheckbox();
        } else {
            $this->addElement('hidden', 'skip_validation', ['value' => 0]);
        }

        $dbResourceForm = new DbResourceForm();
        $this->addElements($dbResourceForm->createElements($formData)->getElements());
        $this->getElement('name')->setValue('icingadb');
        $this->getElement('db')->setMultiOptions([
            'mysql' => 'MySQL',
            //'pgsql' => 'PostgreSQL' TODO: Uncomment once supported
        ]);

        $this->removeElement('name');
        $this->addElement(
            'hidden',
            'name',
            [
                'required'  => true,
                'disabled'  => true,
                'value'     => 'icingadb'
            ]
        );

        if (! isset($formData['db']) || $formData['db'] === 'mysql') {
            $this->getElement('charset')->setValue('utf8mb4');
        }
    }

    public function isValid($formData)
    {
        if (! parent::isValid($formData)) {
            return false;
        }

        if (! isset($formData['skip_validation']) || !$formData['skip_validation']) {
            if (! $this->validateConfiguration()) {
                $this->addSkipValidationCheckbox();
                return false;
            }
        }

        return true;
    }

    public function isValidPartial(array $formData)
    {
        if (isset($formData['backend_validation']) && parent::isValid($formData)) {
            if (! $this->validateConfiguration(true)) {
                return false;
            }

            $this->info(t('The configuration has been successfully validated.'));
        } elseif (! isset($formData['backend_validation'])) {
            // This is usually done by isValid(Partial), but as we're not calling any of these...
            $this->populate($formData);
        }

        return true;
    }

    protected function validateConfiguration(bool $showLog = false): bool
    {
        $inspection = ResourceConfigForm::inspectResource($this);
        if ($inspection !== null) {
            if ($showLog) {
                $join = function ($e) use (&$join) {
                    return is_string($e) ? $e : join("\n", array_map($join, $e));
                };
                $this->addElement(
                    'note',
                    'inspection_output',
                    [
                        'order'         => 0,
                        'value'         => '<strong>' . t('Validation Log') . "</strong>\n\n"
                            . join("\n", array_map($join, $inspection->toArray())),
                        'decorators'    => [
                            'ViewHelper',
                            ['HtmlTag', ['tag' => 'pre', 'class' => 'log-output']],
                        ]
                    ]
                );
            }

            if ($inspection->hasError()) {
                $this->error(sprintf(
                    t('Failed to successfully validate the configuration: %s'),
                    $inspection->getError()
                ));
                return false;
            }
        }

        return true;
    }

    protected function addSkipValidationCheckbox()
    {
        $this->addElement(
            'checkbox',
            'skip_validation',
            [
                'ignore'        => true,
                'label'         => t('Skip Validation'),
                'description'   => t('Check this to not to validate the configuration')
            ]
        );
    }
}
