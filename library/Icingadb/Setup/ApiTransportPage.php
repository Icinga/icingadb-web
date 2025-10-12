<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Setup;

use Icinga\Data\ConfigObject;
use Icinga\Module\Icingadb\Command\Transport\CommandTransport;
use Icinga\Module\Icingadb\Command\Transport\CommandTransportException;
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
            'value'     => 'api'
        ]);
        $this->addElement('hidden', 'name', [
            'required'  => true,
            'disabled'  => true,
            'value'     => 'icinga2'
        ]);
        $this->addElement('text', 'host', [
            'required'      => true,
            'label'         => t('Host'),
            'description'   => t('Hostname or address of the Icinga master')
        ]);
        $this->addElement('number', 'port', [
            'required'          => true,
            'label'             => t('Port'),
            'value'             => 5665,
            'min'               => 1,
            'max'               => 65536
        ]);
        $this->addElement('text', 'username', [
            'required'      => true,
            'label'         => t('API Username'),
            'description'   => t('User to authenticate with using HTTP Basic Auth')
        ]);
        $this->addElement('password', 'password', [
            'required'          => true,
            'renderPassword'    => true,
            'label'             => t('API Password'),
            'autocomplete'      => 'new-password'
        ]);
    }

    public function isValid($formData)
    {
        if (! parent::isValid($formData)) {
            return false;
        }

        if (! isset($formData['skip_validation']) || ! $formData['skip_validation']) {
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

    protected function validateConfiguration(): bool
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
                'ignore'        => true,
                'label'         => t('Skip Validation'),
                'description'   => t('Check this to not to validate the configuration')
            ]
        );
    }
}
