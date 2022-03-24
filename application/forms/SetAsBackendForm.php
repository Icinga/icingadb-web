<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms;

use Icinga\Module\Icingadb\Hook\IcingadbSupportHook;
use Icinga\Web\Session;
use ipl\Web\Compat\CompatForm;

class SetAsBackendForm extends CompatForm
{
    protected $defaultAttributes = [
        'id'    => 'setAsBackendForm',
        'class' => 'icinga-controls'
    ];

    protected function assemble()
    {
        $this->addElement('checkbox', 'backend', [
            'class' => 'autosubmit',
            'label' => t('Use Icinga DB As Backend'),
            'value' => IcingadbSupportHook::isIcingaDbSetAsPreferredBackend()
        ]);
    }

    public function onSuccess()
    {
        Session::getSession()->getNamespace('icingadb')->set(
            IcingadbSupportHook::PREFERENCE_NAME,
            $this->getElement('backend')->isChecked()
        );
    }
}
