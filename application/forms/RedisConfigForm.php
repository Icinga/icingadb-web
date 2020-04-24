<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms;

use Icinga\Data\ResourceFactory;
use Icinga\Forms\ConfigForm;

class RedisConfigForm extends ConfigForm
{
    public function init()
    {
        $this->setSubmitLabel(t('Save Changes'));
    }

    public function createElements(array $formData)
    {
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
}
