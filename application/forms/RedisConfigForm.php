<?php

namespace Icinga\Module\Icingadb\Forms;

use Icinga\Data\ResourceFactory;
use Icinga\Forms\ConfigForm;

class RedisConfigForm extends ConfigForm
{
    public function init()
    {
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    public function createElements(array $formData)
    {
        $this->addElement('text', 'redis1_host', [
            'description' => $this->translate('Redis Host'),
            'label'       => $this->translate('Redis Host'),
            'required'    => true
        ]);

        $this->addElement('number', 'redis1_port', [
            'description' => $this->translate('Redis Port'),
            'label'       => $this->translate('Redis Port'),
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
                'description' => $this->translate(
                    'Redis connection details of your Icinga host. If you are running a high'
                    . ' availability zone with two masters, this is your configuration master.'
                ),
                'legend'      => $this->translate('Primary Icinga Master')
            ]
        );

        $this->addElement('text', 'redis2_host', [
            'description' => $this->translate('Redis Host'),
            'label'       => $this->translate('Redis Host'),
        ]);

        $this->addElement('number', 'redis2_port', [
            'description' => $this->translate('Redis Port'),
            'label'       => $this->translate('Redis Port'),
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
                'description' => $this->translate(
                    'If you are running a high availability zone with two masters,'
                    . ' please provide the Redis connection details of the secondary master.'
                ),
                'legend'      => $this->translate('Secondary Icinga Master')
            ]
        );
    }
}
