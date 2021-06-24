<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms;

use Exception;
use Icinga\Application\Config;
use Icinga\Forms\ConfigForm;
use Icinga\Module\Icingadb\Common\IcingaRedis;
use Icinga\Web\Form;
use Icinga\Web\Form\Element\Checkbox;
use ipl\Validator\PrivateKeyValidator;
use ipl\Validator\X509CertValidator;
use Zend_Validate_Callback;

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

        $this->addElement('hidden', 'redis_insecure');

        if (isset($formData['redis_tls']) && $formData['redis_tls']) {
            $this->addElement('textarea', 'redis_ca', [
                'label'       => t('Redis CA Certificate'),
                'description' => sprintf(
                    t('Verify the peer using this PEM-encoded CA certificate ("%s...")'),
                    '-----BEGIN CERTIFICATE-----'
                ),
                'required'    => true,
                'validators'  => [$this->wrapIplValidator(X509CertValidator::class, 'redis_ca')]
            ]);

            $this->addElement('textarea', 'redis_cert', [
                'label'       => t('Client Certificate'),
                'description' => sprintf(
                    t('Authenticate using this PEM-encoded client certificate ("%s...")'),
                    '-----BEGIN CERTIFICATE-----'
                ),
                'validators'  => [$this->wrapIplValidator(X509CertValidator::class, 'redis_cert')]
            ]);

            $this->addElement('textarea', 'redis_key', [
                'label'       => t('Client Key'),
                'description' => sprintf(
                    t('Authenticate using this PEM-encoded private key ("%s...")'),
                    '-----BEGIN PRIVATE KEY-----'
                ),
                'validators'  => [$this->wrapIplValidator(PrivateKeyValidator::class, 'redis_key')]
            ]);
        } else {
            $this->addElement('hidden', 'redis_ca');
            $this->addElement('hidden', 'redis_cert');
            $this->addElement('hidden', 'redis_key');
        }

        $this->addDisplayGroup(
            ['redis_tls', 'redis_insecure', 'redis_ca', 'redis_cert', 'redis_key'],
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

        if (isset($formData['redis_insecure']) && $formData['redis_insecure']) {
            // In case another error occured and the checkbox was displayed before
            static::addInsecureCheckboxIfTls($this, $formData);
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
                    'Secure connections. If you are running a high'
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

    public static function addInsecureCheckboxIfTls(Form $form, array $formData = null)
    {
        if (
            $formData === null
                ? $form->getElement('redis_tls')->getValue()
                : isset($formData['redis_tls']) && $formData['redis_tls']
        ) {
            $displayGroup = $form->getDisplayGroup('redis');
            $elements = $displayGroup->getElements();

            $value = $formData === null
                ? $form->getElement('redis_insecure')->getValue()
                : isset($formData['redis_insecure']) && $formData['redis_insecure'];

            $form->removeElement('redis_insecure');

            $form->addElement(
                'checkbox',
                'redis_insecure',
                [
                    'label'       => t('Insecure'),
                    'description' => t('Don\'t verify the peer'),
                    'value'       => $value
                ]
            );

            $elements['redis_insecure'] = $form->getElement('redis_insecure');
            $displayGroup->setElements($elements);
        }
    }

    public function isValid($formData)
    {
        if (! parent::isValid($formData)) {
            return false;
        }

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

        if (($el = $this->getElement('skip_validation')) === null || ! $el->isChecked()) {
            if (! static::checkRedis($this)) {
                if ($el === null) {
                    static::addSkipValidationCheckbox($this);
                    static::addInsecureCheckboxIfTls($this);
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

        foreach (ConfigForm::transformEmptyValuesToNull($form->getValues()) as $sectionAndPropertyName => $value) {
            if ($value !== null) {
                list($section, $property) = explode('_', $sectionAndPropertyName, 2);
                $sections[$section][$property] = $value;
            }
        }

        foreach ($sections as $section => $options) {
            $config->setSection($section, $options);
        }

        try {
            $redis1 = $form->getPrimaryRedis($config);
        } catch (Exception $e) {
            $form->warning(sprintf(
                t('Failed to connect to primary Redis: %s'),
                $e->getMessage()
            ));
            return false;
        }

        if ($form->getLastIcingaHeartbeat($redis1) === null) {
            $form->warning(t('Primary connection established but failed to verify Icinga is connected as well.'));
            return false;
        }

        try {
            $redis2 = $form->getSecondaryRedis($config);
        } catch (Exception $e) {
            $form->warning(sprintf(t('Failed to connect to secondary Redis: %s'), $e->getMessage()));
            return false;
        }

        if ($redis2 !== null && $form->getLastIcingaHeartbeat($redis2) === null) {
            $form->warning(t('Secondary connection established but failed to verify Icinga is connected as well.'));
            return false;
        }

        $form->info(t('The configuration has been successfully validated.'));
        return true;
    }

    /**
     * Wraps the given IPL validator class into a callback validator
     * for usage as the only validator of the element given by name.
     *
     * @param   string  $cls        IPL validator class FQN
     * @param   string  $element    Form element name
     *
     * @return  array               Callback validator
     */
    private function wrapIplValidator($cls, $element)
    {
        return [
            'Callback',
            false,
            [
                'callback' => function ($v) use ($cls, $element) {
                    $validator = new $cls();
                    $valid = $validator->isValid($v);

                    if (! $valid) {
                        /** @var Zend_Validate_Callback $callbackValidator */
                        $callbackValidator = $this->getElement($element)->getValidator('Callback');

                        $callbackValidator->setMessage(
                            $validator->getMessages()[0],
                            Zend_Validate_Callback::INVALID_VALUE
                        );
                    }

                    return $valid;
                }
            ]
        ];
    }
}
