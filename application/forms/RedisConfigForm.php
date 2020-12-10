<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms;

use Exception;
use Icinga\Application\Config;
use Icinga\Forms\ConfigForm;
use Icinga\Module\Icingadb\Common\IcingaRedis;
use Icinga\Module\Icingadb\Web\Form\Validator\TlsCertValidator;
use Icinga\Module\Icingadb\Web\Form\Validator\TlsKeyValidator;
use Icinga\Web\Form\Element\Checkbox;

class RedisConfigForm extends ConfigForm
{
    use IcingaRedis;

    public function init()
    {
        $this->setSubmitLabel(t('Save Changes'));
        $this->setValidatePartial(true);
    }

    public function createElements(array $formData)
    {
        $this->addElement('checkbox', 'redis_tls', [
            'label'       => t('Use TLS'),
            'description' => t('Encrypt connections to Redis via TLS'),
            'autosubmit'  => true
        ]);

        if (isset($formData['redis_tls']) && $formData['redis_tls']) {
            $this->addElement('textarea', 'redis_cert', [
                'label'       => t('Client Certificate'),
                'description' => sprintf(
                    t('Authenticate using this PEM-encoded client certificate ("%s...")'),
                    '-----BEGIN CERTIFICATE-----'
                ),
                'validators'  => [new TlsCertValidator()]
            ]);

            $this->addElement('textarea', 'redis_key', [
                'label'       => t('Client Key'),
                'description' => sprintf(
                    t('Authenticate using this PEM-encoded private key ("%s...")'),
                    '-----BEGIN PRIVATE KEY-----'
                ),
                'validators'  => [new TlsKeyValidator()]
            ]);

            $this->addElement('textarea', 'redis_ca', [
                'label'       => t('Redis CA Certificate'),
                'description' => sprintf(
                    t('Verify the peer using this PEM-encoded CA certificate ("%s...")'),
                    '-----BEGIN CERTIFICATE-----'
                ),
                'validators'  => [new TlsCertValidator()]
            ]);
        } else {
            $this->addElement('hidden', 'redis_cert');
            $this->addElement('hidden', 'redis_key');
            $this->addElement('hidden', 'redis_ca');
        }

        $this->addDisplayGroup(
            ['redis_tls', 'redis_cert', 'redis_key', 'redis_ca'],
            'redis',
            [
                'decorators'  => [
                    'FormElements',
                    ['HtmlTag', ['tag' => 'div']],
                    [
                        'Description',
                        ['tag' => 'span', 'class' => 'description', 'placement' => 'prepend']
                    ],
                    'Fieldset'
                ],
                'description' => t(
                    'Redis connection details of your Icinga host. If you are running a high'
                    . ' availability zone with two masters, the following applies to both of them.'
                ),
                'legend'      => t('General')
            ]
        );

        if (isset($formData['skip_validation']) && $formData['skip_validation']) {
            // In case another error occured and the checkbox was displayed before
            static::addSkipValidationCheckbox($this);
        }

        $this->addElement('text', 'redis1_host', [
            'description' => t('Redis Host'),
            'label'       => t('Redis Host'),
            'required'    => true
        ]);

        $this->addElement('number', 'redis1_port', [
            'description' => t('Redis Port'),
            'label'       => t('Redis Port'),
            'placeholder' => 6380
        ]);

        $this->addDisplayGroup(
            ['redis1_host', 'redis1_port'],
            'redis1',
            [
                'decorators'  => [
                    'FormElements',
                    ['HtmlTag', ['tag' => 'div']],
                    [
                        'Description',
                        ['tag' => 'span', 'class' => 'description', 'placement' => 'prepend']
                    ],
                    'Fieldset'
                ],
                'description' => t(
                    'Redis connection details of your Icinga host. If you are running a high'
                    . ' availability zone with two masters, this is your configuration master.'
                ),
                'legend'      => t('Primary Icinga Master')
            ]
        );

        $this->addElement('text', 'redis2_host', [
            'description' => t('Redis Host'),
            'label'       => t('Redis Host'),
        ]);

        $this->addElement('number', 'redis2_port', [
            'description' => t('Redis Port'),
            'label'       => t('Redis Port'),
            'placeholder' => 6380
        ]);

        $this->addDisplayGroup(
            ['redis2_host', 'redis2_port'],
            'redis2',
            [
                'decorators'  => [
                    'FormElements',
                    ['HtmlTag', ['tag' => 'div']],
                    [
                        'Description',
                        ['tag' => 'span', 'class' => 'description', 'placement' => 'prepend']
                    ],
                    'Fieldset'
                ],
                'description' => t(
                    'If you are running a high availability zone with two masters,'
                    . ' please provide the Redis connection details of the secondary master.'
                ),
                'legend'      => t('Secondary Icinga Master')
            ]
        );
    }

    public static function addSkipValidationCheckbox($form)
    {
        $form->addElement(
            'checkbox',
            'skip_validation',
            [
                'order'         => 0,
                'ignore'        => true,
                'label'         => t('Skip Validation'),
                'description'   => t(
                    'Check this box to enforce changes without validating that Redis is available.'
                )
            ]
        );
    }

    public function isValid($formData)
    {
        if (! parent::isValid($formData)) {
            return false;
        }

        if (($el = $this->getElement('skip_validation')) === null || ! $el->isChecked()) {
            if (! static::checkRedis($this)) {
                if ($el === null) {
                    static::addSkipValidationCheckbox($this);
                }

                return false;
            }
        }

        return true;
    }

    public function isValidPartial(array $formData)
    {
        if (! parent::isValidPartial($formData)) {
            return false;
        }

        if ($this->getElement('backend_validation')->isChecked()) {
            return static::checkRedis($this);
        }

        return true;
    }

    public function onSuccess()
    {
        /** @var Checkbox $useTls */
        $useTls = $this->getElement('redis_tls');

        if ($useTls !== null && $useTls->isChecked()) {
            $cert = $this->getElement('redis_cert');
            $key = $this->getElement('redis_key');

            if (($cert !== null && $cert->getValue() !== '') !== ($key !== null && $key->getValue() !== '')) {
                $this->addError(t(
                    'Either both a client certificate and its private key or none of them must be specified'
                ));

                return false;
            }
        }

        return parent::onSuccess();
    }

    public function addSubmitButton()
    {
        parent::addSubmitButton()
            ->getElement('btn_submit')
            ->setDecorators(['ViewHelper']);

        $this->addElement(
            'submit',
            'backend_validation',
            [
                'ignore'                => true,
                'label'                 => t('Validate Configuration'),
                'data-progress-label'   => t('Validation In Progress'),
                'decorators'            => ['ViewHelper']
            ]
        );
        $this->addDisplayGroup(
            ['btn_submit', 'backend_validation'],
            'submit_validation',
            [
                'decorators' => [
                    'FormElements',
                    ['HtmlTag', ['tag' => 'div', 'class' => 'control-group form-controls']]
                ]
            ]
        );

        return $this;
    }

    public static function checkRedis($form)
    {
        $sections = [];
        $config = new Config();

        foreach (static::transformEmptyValuesToNull($form->getValues()) as $sectionAndPropertyName => $value) {
            if ($value !== null) {
                list($section, $property) = explode('_', $sectionAndPropertyName, 2);
                $sections[$section][$property] = $value;
            }
        }

        foreach ($sections as $section => $options) {
            $config->setSection($section, $options);
        }

        try {
            $redis1 = static::getPrimaryRedis($config);
        } catch (Exception $e) {
            $form->warning(sprintf(
                t('Failed to connect to primary Redis: %s'),
                $e->getMessage()
            ));
            return false;
        }

        if (static::getLastIcingaHeartbeat($redis1) === null) {
            $form->warning(t('Primary connection established but failed to verify Icinga is connected as well.'));
            return false;
        }

        try {
            $redis2 = static::getSecondaryRedis($config);
        } catch (Exception $e) {
            $form->warning(sprintf(t('Failed to connect to secondary Redis: %s'), $e->getMessage()));
            return false;
        }

        if ($redis2 !== null && static::getLastIcingaHeartbeat($redis2) === null) {
            $form->warning(t('Secondary connection established but failed to verify Icinga is connected as well.'));
            return false;
        }

        $form->info(t('The configuration has been successfully validated.'));
        return true;
    }
}
