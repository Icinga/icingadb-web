<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms;

use Exception;
use Icinga\Application\Config;
use Icinga\Authentication\Auth;
use Icinga\Data\ConfigObject;
use Icinga\Module\Icingadb\Hook\IcingadbSupportHook;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Web\Form;
use Icinga\Web\Notification;
use Icinga\Web\Session;

class SetAsBackendConfigForm extends Form
{
    public function createElements(array $formData)
    {
        $this->addElement('checkbox', 'backend', [
            'label'         => t('Use icinga DB as backend for all modules'),
            'description'   => t('Use icinga db as backend resource for all modules'),
            'value'         => IcingadbSupportHook::isIcingaDbSetAsPreferredBackend(),
        ]);

        $this->addElement(
            'submit',
            'btn_submit',
            array(
                'ignore'        => true,
                'label'         => t('Save to the Preferences'),
                'decorators'    => array('ViewHelper'),
                'class'         => 'btn-primary'
            )
        );

        $this->addElement(
            'submit',
            'btn_submit_session',
            array(
                'ignore'        => true,
                'label'         => t('Save for the current Session'),
                'decorators'    => array('ViewHelper')
            )
        );

        $this->setAttrib('data-progress-element', 'preferences-progress');
        $this->addElement(
            'note',
            'preferences-progress',
            array(
                'decorators'    => array(
                    'ViewHelper',
                    array('Spinner', array('id' => 'preferences-progress'))
                )
            )
        );

        $this->addDisplayGroup(
            array('btn_submit', 'btn_submit_session', 'preferences-progress'),
            'submit_buttons',
            array(
                'decorators' => array(
                    'FormElements',
                    array('HtmlTag', array('tag' => 'div', 'class' => 'control-group form-controls'))
                )
            )
        );
    }

    public function onSuccess()
    {
        $this->save($this->getElement('backend')->isChecked());
    }

    public function save($setAsBackend)
    {
        $user = Auth::getInstance()->getUser();
        try {
            $preferencesStore = PreferencesStore::create(new ConfigObject([
                'store'     => Config::app()->get('global', 'config_backend', 'db'),
                'resource'  => Config::app()->get('global', 'config_resource')
            ]), $user);
            $preferences = new Preferences($preferencesStore->load());
            $webPreferences = $user->getPreferences()->get('icingaweb');

            $webPreferences[IcingadbSupportHook::PREFERENCE_NAME] = $setAsBackend;
            $preferences->icingaweb = $webPreferences;
        } catch (Exception $e) {
            Notification::error('Failed to save the preference');
            return;
        }

        Session::getSession()->user->setPreferences($preferences);

        if ($this->getElement('btn_submit') !== null && $this->getElement('btn_submit')->isChecked()) {
            $preferencesStore->save($preferences);
            Notification::success($this->translate('Preference successfully saved'));
        } else {
            Notification::success($this->translate('Preference successfully saved for the current session'));
        }
    }
}
