<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms;

use Closure;
use Exception;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Exception\NotWritableError;
use Icinga\File\Storage\LocalFileStorage;
use Icinga\File\Storage\TemporaryLocalFileStorage;
use Icinga\Forms\ConfigForm;
use Icinga\Module\Icingadb\Common\IcingaRedis;
use Icinga\Web\Form;
use ipl\Validator\PrivateKeyValidator;
use ipl\Validator\X509CertValidator;
use Zend_Validate_Callback;

class RedisConfigForm extends ConfigForm
{
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

        $this->addElement('hidden', 'redis_ca');
        $this->addElement('hidden', 'redis_cert');
        $this->addElement('hidden', 'redis_key');
        $this->addElement('hidden', 'clear_redis_ca', ['ignore' => true]);
        $this->addElement('hidden', 'clear_redis_cert', ['ignore' => true]);
        $this->addElement('hidden', 'clear_redis_key', ['ignore' => true]);

        $useTls = isset($formData['redis_tls']) && $formData['redis_tls'];
        if ($useTls) {
            $this->addElement('textarea', 'redis_ca_pem', [
                'label'       => t('Redis CA Certificate'),
                'description' => sprintf(
                    t('Verify the peer using this PEM-encoded CA certificate ("%s...")'),
                    '-----BEGIN CERTIFICATE-----'
                ),
                'required'    => true,
                'ignore'      => true,
                'validators'  => [$this->wrapIplValidator(X509CertValidator::class, 'redis_ca_pem')]
            ]);

            $this->addElement('textarea', 'redis_cert_pem', [
                'label'       => t('Client Certificate'),
                'description' => sprintf(
                    t('Authenticate using this PEM-encoded client certificate ("%s...")'),
                    '-----BEGIN CERTIFICATE-----'
                ),
                'ignore'      => true,
                'allowEmpty'  => false,
                'validators'  => [
                    $this->wrapIplValidator(X509CertValidator::class, 'redis_cert_pem', function ($value) {
                        if (! $value && $this->getElement('redis_key_pem')->getValue()) {
                            $this->getElement('redis_cert_pem')->addError(t(
                                'Either both a client certificate and its private key or none of them must be specified'
                            ));
                        }

                        return true;
                    })
                ]
            ]);

            $this->addElement('textarea', 'redis_key_pem', [
                'label'       => t('Client Key'),
                'description' => sprintf(
                    t('Authenticate using this PEM-encoded private key ("%s...")'),
                    '-----BEGIN PRIVATE KEY-----'
                ),
                'ignore'      => true,
                'allowEmpty'  => false,
                'validators'  => [
                    $this->wrapIplValidator(PrivateKeyValidator::class, 'redis_key_pem', function ($value) {
                        if (! $value && $this->getElement('redis_cert_pem')->getValue()) {
                            $this->getElement('redis_key_pem')->addError(t(
                                'Either both a client certificate and its private key or none of them must be specified'
                            ));
                        }

                        return true;
                    })
                ]
            ]);
        }

        $this->addDisplayGroup(
            ['redis_tls', 'redis_insecure', 'redis_ca_pem', 'redis_cert_pem', 'redis_key_pem'],
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
                    'Secure connections. If you are running a high availability zone'
                    . ' with two masters, the following applies to both of them.'
                ),
                'legend'      => t('General')
            ]
        );

        if (isset($formData['skip_validation']) && $formData['skip_validation']) {
            // In case another error occured and the checkbox was displayed before
            static::addSkipValidationCheckbox($this);
        }

        if ($useTls && isset($formData['redis_insecure']) && $formData['redis_insecure']) {
            // In case another error occured and the checkbox was displayed before
            static::addInsecureCheckboxIfTls($this);
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

        $this->addElement('password', 'redis1_password', [
            'description'    => t('Redis Password'),
            'label'          => t('Redis Password'),
            'renderPassword' => true,
            'autocomplete'   => 'new-password'
        ]);

        $this->addDisplayGroup(
            ['redis1_host', 'redis1_port', 'redis1_password'],
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

        $this->addElement('password', 'redis2_password', [
            'description'    => t('Redis Password'),
            'label'          => t('Redis Password'),
            'renderPassword' => true,
            'autocomplete'   => 'new-password'
        ]);

        $this->addDisplayGroup(
            ['redis2_host', 'redis2_port', 'redis2_password'],
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

    public static function addSkipValidationCheckbox(Form $form)
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

    public static function addInsecureCheckboxIfTls(Form $form)
    {
        if ($form->getElement('redis_insecure') !== null) {
            return;
        }

        $form->addElement(
            'checkbox',
            'redis_insecure',
            [
                'order'       => 1,
                'label'       => t('Insecure'),
                'description' => t('Don\'t verify the peer')
            ]
        );

        $displayGroup = $form->getDisplayGroup('redis');
        $elements = $displayGroup->getElements();
        $elements['redis_insecure'] = $form->getElement('redis_insecure');
        $displayGroup->setElements($elements);
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

                    if ($this->getElement('redis_tls')->isChecked()) {
                        static::addInsecureCheckboxIfTls($this);
                    }
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

        $useTls = $this->getElement('redis_tls')->isChecked();
        foreach (['ca', 'cert', 'key'] as $name) {
            $textareaName = 'redis_' . $name . '_pem';
            $clearName = 'clear_redis_' . $name;

            if ($useTls) {
                $this->getElement($clearName)->setValue(null);

                $pemPath = $this->getValue('redis_' . $name);
                if ($pemPath && ! isset($formData[$textareaName]) && ! $formData[$clearName]) {
                    $this->getElement($textareaName)->setValue(@file_get_contents($pemPath));
                }
            }

            if (isset($formData[$textareaName]) && ! $formData[$textareaName]) {
                $this->getElement($clearName)->setValue(true);
            }
        }

        if ($this->getElement('backend_validation')->isChecked()) {
            if (! static::checkRedis($this)) {
                if ($this->getElement('redis_tls')->isChecked()) {
                    static::addInsecureCheckboxIfTls($this);
                }

                return false;
            }
        }

        return true;
    }

    public function onRequest()
    {
        $errors = [];

        $redisConfig = $this->config->getSection('redis');
        if ($redisConfig->get('tls', false)) {
            foreach (['ca', 'cert', 'key'] as $name) {
                $path = $redisConfig->get($name);
                if (file_exists($path)) {
                    try {
                        $redisConfig[$name . '_pem'] = file_get_contents($path);
                    } catch (Exception $e) {
                        $errors['redis_' . $name . '_pem'] = sprintf(
                            t('Failed to read file "%s": %s'),
                            $path,
                            $e->getMessage()
                        );
                    }
                }
            }
        }

        $connectionConfig = Config::fromIni(
            join(DIRECTORY_SEPARATOR, [dirname($this->config->getConfigFile()), 'redis.ini'])
        );
        $this->config->setSection('redis1', [
            'host'      => $connectionConfig->get('redis1', 'host'),
            'port'      => $connectionConfig->get('redis1', 'port'),
            'password'  => $connectionConfig->get('redis1', 'password')
        ]);
        $this->config->setSection('redis2', [
            'host'      => $connectionConfig->get('redis2', 'host'),
            'port'      => $connectionConfig->get('redis2', 'port'),
            'password'  => $connectionConfig->get('redis2', 'password')
        ]);

        parent::onRequest();

        foreach ($errors as $elementName => $message) {
            $this->getElement($elementName)->addError($message);
        }
    }

    public function onSuccess()
    {
        $storage = new LocalFileStorage(Icinga::app()->getStorageDir(
            join(DIRECTORY_SEPARATOR, ['modules', 'icingadb', 'redis'])
        ));

        $useTls = $this->getElement('redis_tls')->isChecked();
        $pem = null;
        foreach (['ca', 'cert', 'key'] as $name) {
            $textarea = $this->getElement('redis_' . $name . '_pem');
            if ($useTls && $textarea !== null && ($pem = $textarea->getValue())) {
                $pemFile = md5($pem) . '-' . $name . '.pem';
                if (! $storage->has($pemFile)) {
                    try {
                        $storage->create($pemFile, $pem);
                    } catch (NotWritableError $e) {
                        $textarea->addError($e->getMessage());
                        return false;
                    }
                }

                $this->getElement('redis_' . $name)->setValue($storage->resolvePath($pemFile));
            }

            if ((! $useTls && $this->getElement('clear_redis_' . $name)->getValue()) || ($useTls && ! $pem)) {
                $pemPath = $this->getValue('redis_' . $name);
                if ($pemPath && $storage->has(($pemFile = basename($pemPath)))) {
                    try {
                        $storage->delete($pemFile);
                        $this->getElement('redis_' . $name)->setValue(null);
                    } catch (NotWritableError $e) {
                        $this->addError($e->getMessage());
                        return false;
                    }
                }
            }
        }

        $connectionConfig = Config::fromIni(
            join(DIRECTORY_SEPARATOR, [dirname($this->config->getConfigFile()), 'redis.ini'])
        );

        $redis1Host = $this->getValue('redis1_host');
        $redis1Port = $this->getValue('redis1_port');
        $redis1Password = $this->getValue('redis1_password');
        $redis1Section = $connectionConfig->getSection('redis1');
        $redis1Section['host'] = $redis1Host;
        $this->getElement('redis1_host')->setValue(null);
        $connectionConfig->setSection('redis1', $redis1Section);
        if (! empty($redis1Port)) {
            $redis1Section['port'] = $redis1Port;
            $this->getElement('redis1_port')->setValue(null);
        } else {
            $redis1Section['port'] = null;
        }

        if (! empty($redis1Password)) {
            $redis1Section['password'] = $redis1Password;
            $this->getElement('redis1_password')->setValue(null);
        } else {
            $redis1Section['password'] = null;
        }

        if (! array_filter($redis1Section->toArray())) {
            $connectionConfig->removeSection('redis1');
        }

        $redis2Host = $this->getValue('redis2_host');
        $redis2Port = $this->getValue('redis2_port');
        $redis2Password = $this->getValue('redis2_password');
        $redis2Section = $connectionConfig->getSection('redis2');
        if (! empty($redis2Host)) {
            $redis2Section['host'] = $redis2Host;
            $this->getElement('redis2_host')->setValue(null);
            $connectionConfig->setSection('redis2', $redis2Section);
        } else {
            $redis2Section['host'] = null;
        }

        if (! empty($redis2Port)) {
            $redis2Section['port'] = $redis2Port;
            $this->getElement('redis2_port')->setValue(null);
            $connectionConfig->setSection('redis2', $redis2Section);
        } else {
            $redis2Section['port'] = null;
        }

        if (! empty($redis2Password)) {
            $redis2Section['password'] = $redis2Password;
            $this->getElement('redis2_password')->setValue(null);
        } else {
            $redis2Section['password'] = null;
        }

        if (! array_filter($redis2Section->toArray())) {
            $connectionConfig->removeSection('redis2');
        }

        $connectionConfig->saveIni();

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

    public static function checkRedis(Form $form): bool
    {
        $sections = [];

        $storage = new TemporaryLocalFileStorage();
        foreach (ConfigForm::transformEmptyValuesToNull($form->getValues()) as $sectionAndPropertyName => $value) {
            if ($value !== null) {
                list($section, $property) = explode('_', $sectionAndPropertyName, 2);
                if (in_array($property, ['ca', 'cert', 'key'])) {
                    $storage->create("$property.pem", $value);
                    $value = $storage->resolvePath("$property.pem");
                }

                $sections[$section][$property] = $value;
            }
        }

        $ignoredTextAreas = [
            'ca'    => 'redis_ca_pem',
            'cert'  => 'redis_cert_pem',
            'key'   => 'redis_key_pem'
        ];
        foreach ($ignoredTextAreas as $name => $textareaName) {
            if (($textarea = $form->getElement($textareaName)) !== null) {
                if (($pem = $textarea->getValue())) {
                    if ($storage->has("$name.pem")) {
                        $storage->update("$name.pem", $pem);
                    } else {
                        $storage->create("$name.pem", $pem);
                        $sections['redis'][$name] = $storage->resolvePath("$name.pem");
                    }
                } elseif ($storage->has("$name.pem")) {
                    $storage->delete("$name.pem");
                    unset($sections['redis'][$name]);
                }
            }
        }

        $moduleConfig = new Config();
        $moduleConfig->setSection('redis', $sections['redis']);
        $redisConfig = new Config();
        $redisConfig->setSection('redis1', $sections['redis1'] ?? []);
        $redisConfig->setSection('redis2', $sections['redis2'] ?? []);

        try {
            $redis1 = IcingaRedis::getPrimaryRedis($moduleConfig, $redisConfig);
        } catch (Exception $e) {
            $form->warning(sprintf(
                t('Failed to connect to primary Redis: %s'),
                $e->getMessage()
            ));
            return false;
        }

        if (IcingaRedis::getLastIcingaHeartbeat($redis1) === null) {
            $form->warning(t('Primary connection established but failed to verify Icinga is connected as well.'));
            return false;
        }

        try {
            $redis2 = IcingaRedis::getSecondaryRedis($moduleConfig, $redisConfig);
        } catch (Exception $e) {
            $form->warning(sprintf(t('Failed to connect to secondary Redis: %s'), $e->getMessage()));
            return false;
        }

        if ($redis2 !== null && IcingaRedis::getLastIcingaHeartbeat($redis2) === null) {
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
     * @param   Closure $additionalValidator
     *
     * @return  array               Callback validator
     */
    private function wrapIplValidator(string $cls, string $element, Closure $additionalValidator = null): array
    {
        return [
            'Callback',
            false,
            [
                'callback' => function ($v) use ($cls, $element, $additionalValidator) {
                    if ($additionalValidator !== null) {
                        if (! $additionalValidator($v)) {
                            return false;
                        }
                    }

                    if (! $v) {
                        return true;
                    }

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
