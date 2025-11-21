<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Setup;

use Icinga\Web\Form;

class WelcomePage extends Form
{
    public function init()
    {
        $this->setName('setup_icingadb_welcome');
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'note',
            'welcome',
            array(
                'value'         => t(
                    'Welcome to the configuration of Icinga DB Web!'
                ),
                'decorators'    => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'h2'))
                )
            )
        );

        $this->addElement(
            'note',
            'description_1',
            array(
                'value'         => '<p>' . t(
                    'Icinga DB Web is the UI for Icinga DB and provides'
                    . ' a graphical interface to your monitoring environment.'
                ) . '</p>',
                'decorators'    => array('ViewHelper')
            )
        );

        $this->addElement(
            'note',
            'description_2',
            array(
                'value'         => '<p>' . t(
                    'The wizard will guide you through the configuration to'
                    . ' establish a connection with Icinga DB and Icinga 2.'
                ) . '</p>',
                'decorators'    => array('ViewHelper')
            )
        );
    }
}
