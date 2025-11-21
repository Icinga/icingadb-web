<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Forms;

use Icinga\Data\ResourceFactory;
use Icinga\Forms\ConfigForm;

class DatabaseConfigForm extends ConfigForm
{
    public function init()
    {
        $this->setSubmitLabel(t('Save Changes'));
    }

    public function createElements(array $formData)
    {
        $dbResources = ResourceFactory::getResourceConfigs('db')->keys();

        $this->addElement('select', 'icingadb_resource', [
            'description'   => t('Database resource'),
            'label'         => t('Database'),
            'multiOptions'  => array_merge(
                ['' => sprintf(' - %s - ', t('Please choose'))],
                array_combine($dbResources, $dbResources)
            ),
            'disable'       => [''],
            'required'      => true,
            'value'         => ''
        ]);
    }
}
