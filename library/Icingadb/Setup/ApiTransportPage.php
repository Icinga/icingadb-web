<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Setup;

use Icinga\Data\ConfigObject;
use Icinga\Module\Monitoring\Command\Transport\ApiCommandTransport;
use Icinga\Module\Monitoring\Command\Transport\CommandTransport;
use Icinga\Module\Monitoring\Exception\CommandTransportException;
use Icinga\Module\Monitoring\Forms\Config\Transport\ApiTransportForm;
use Icinga\Web\Form;

class ApiTransportPage extends Form
{
    public function init()
    {
        $this->setName('setup_icingadb_api_transport');
        $this->setTitle(t('Icinga 2 API'));
        $this->addDescription(t(
            'Please fill out the connection details to the Icinga 2 API.'
        ));
        $this->setValidatePartial(true);
    }

    public function createElements(array $formData)
    {
        if (isset($formData['skip_validation']) && $formData['skip_validation']) {
            // In case another error occured and the checkbox was displayed before
            $this->addSkipValidationCheckbox();
        } else {
            $this->addElement('hidden', 'skip_validation', ['value' => 0]);
        }

        $this->addElement('hidden', 'transport', [
            'required'  => true,
            'disabled'  => true,
            'value'     => ApiCommandTransport::TRANSPORT
        ]);
        $this->addElement('hidden', 'name', [
            'required'  => true,
            'disabled'  => true,
            'value'     => 'icinga2'
        ]);

        $transportForm = new ApiTransportForm();
        $transportForm->createElements($formData);
        $this->addElements($transportForm->getElements());
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
            if (! $this->validateConfiguration()) {
                return false;
            }

            $this->info(t('The configuration has been successfully validated.'));
        } elseif (! isset($formData['backend_validation'])) {
            // This is usually done by isValid(Partial), but as we're not calling any of these...
            $this->populate($formData);
        }

        return true;
    }

    protected function validateConfiguration()
    {
        try {
            CommandTransport::createTransport(new ConfigObject($this->getValues()))->probe();
        } catch (CommandTransportException $e) {
            $this->error(sprintf(
                t('Failed to successfully validate the configuration: %s'),
                $e->getMessage()
            ));

            return false;
        }

        return true;
    }

    protected function addSkipValidationCheckbox()
    {
        $this->addElement(
            'checkbox',
            'skip_validation',
            [
                'label'         => t('Skip Validation'),
                'description'   => t('Check this to not to validate the configuration')
            ]
        );
    }
}
