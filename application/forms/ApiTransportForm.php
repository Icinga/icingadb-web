<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms;

use Icinga\Data\ConfigObject;
use Icinga\Module\Icingadb\Command\Transport\CommandTransport;
use Icinga\Module\Icingadb\Command\Transport\CommandTransportException;
use Icinga\Web\Session;
use ipl\Validator\CallbackValidator;
use ipl\Validator\X509CertValidator;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Compat\CompatForm;
use Throwable;

class ApiTransportForm extends CompatForm
{
    use CsrfCounterMeasure;

    protected function assemble()
    {
        // TODO: Use a validator to check if a name is not already in use
        $this->addElement('text', 'name', [
            'required'      => true,
            'label'         => t('Transport Name')
        ]);

        $this->addElement('hidden', 'transport', [
            'value' => 'api'
        ]);

        $this->addElement('text', 'host', [
            'required'      => true,
            'id'            => 'api_transport_host',
            'label'         => t('Host'),
            'description'   => t('Hostname or address of the Icinga master')
        ]);

        $this->addElement('text', 'caPath', [
            'required'      => false,
            'label'         => t('Verify Peer'),
            'description'   => t('Path to a certificate file to verify the Icinga master\'s api certificate'),
            'placeholder'   => t('Leave empty to disable peer verification'),
            'validators'    => [new CallbackValidator(function (?string $value, CallbackValidator $validator) {
                if (empty($value)) {
                    return true;
                }

                if (! file_exists($value) || ! is_readable($value)) {
                    $validator->addMessage(t('The specified certificate file does not exist or is not readable'));

                    return false;
                }

                try {
                    $cert = file_get_contents($value);
                } catch (Throwable $e) {
                    $validator->addMessage(t('Failed to read certificate file: %s', $e->getMessage()));

                    return false;
                }

                $x509Validator = new X509CertValidator();
                if (! $x509Validator->isValid($cert)) {
                    $validator->addMessages($x509Validator->getMessages());

                    return false;
                }

                return true;
            })]
        ]);

        // TODO: Don't rely only on browser validation
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
            'required'      => true,
            'autocomplete'  => 'new-password',
            'label'         => t('API Password')
        ]);

        $this->addElement('submit', 'btn_submit', [
            'label' => t('Save')
        ]);

        $this->addElement($this->createCsrfCounterMeasure(Session::getSession()->getId()));
    }

    public function validate()
    {
        parent::validate();
        if (! $this->isValid) {
            return $this;
        }

        if ($this->getPopulatedValue('force_creation') === 'y') {
            return $this;
        }

        try {
            CommandTransport::createTransport(new ConfigObject($this->getValues()))->probe();
        } catch (CommandTransportException $e) {
            $this->addMessage(
                sprintf(t('Failed to successfully validate the configuration: %s'), $e->getMessage())
            );

            $forceCheckbox = $this->createElement(
                'checkbox',
                'force_creation',
                [
                    'ignore'        => true,
                    'label'         => t('Force Changes'),
                    'description'   => t('Check this box to enforce changes without connectivity validation')
                ]
            );

            $this->registerElement($forceCheckbox);
            $this->decorate($forceCheckbox);
            $this->prepend($forceCheckbox);

            $this->isValid = false;
        }

        return $this;
    }
}
