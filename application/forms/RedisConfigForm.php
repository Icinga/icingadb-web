<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms;

use Exception;
use Icinga\Forms\ConfigForm;
use Redis;

class RedisConfigForm extends ConfigForm
{
    public function init()
    {
        $this->setSubmitLabel(t('Save Changes'));
        $this->setValidatePartial(true);
    }

    public function createElements(array $formData)
    {
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
        $streamy = version_compare(phpversion('redis'), '4.3.0', '>=');
        $priPort = $form->getElement('redis1_port')->getValue();

        try {
            $redis1 = new Redis();
            $redis1->connect(
                $form->getElement('redis1_host')->getValue(),
                empty($priPort) ? 6380 : $priPort,
                0.5
            );
        } catch (Exception $e) {
            $form->warning(sprintf(
                t('Failed to connect to primary Redis: %s'),
                $e->getMessage()
            ));
            return false;
        }

        if ($streamy) {
            $rs = $redis1->xRead(['icinga:stats' => 0], 1);
            if (empty($rs)) {
                $form->warning(t(
                    'Primary connection established but failed to verify Icinga is connected as well.'
                ));
                return false;
            }
        }

        if (($secHost = $form->getElement('redis2_host')->getValue())) {
            $secPort = $form->getElement('redis2_port')->getValue();

            try {
                $redis2 = new Redis();
                $redis2->connect(
                    $secHost,
                    empty($secPort) ? 6380 : $secPort,
                    0.5
                );
            } catch (Exception $e) {
                $form->warning(sprintf(
                    t('Failed to connect to secondary Redis: %s'),
                    $e->getMessage()
                ));
                return false;
            }

            if ($streamy) {
                $rs = $redis2->xRead(['icinga:stats' => 0], 1);
                if (empty($rs)) {
                    $form->warning(t(
                        'Secondary connection established but failed to verify Icinga is connected as well.'
                    ));
                    return false;
                }
            }
        }

        $form->info(t('The configuration has been successfully validated.'));
        return true;
    }
}
