<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms;

class SetAsBackendForm extends SetAsBackendConfigForm
{
    public function init()
    {
        $this->setName('IcingaModuleIcingadbFormsSetAsBackendForm');
        $this->setTokenDisabled();
        // If you change name here, please change in migration.js also.
    }

    public function createElements(array $formData)
    {
        parent::createElements([]);

        $this->removeElement('btn_submit');
        $this->removeElement('btn_submit_session');
    }
}
