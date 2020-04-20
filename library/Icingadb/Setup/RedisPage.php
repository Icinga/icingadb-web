<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Setup;

use Icinga\Module\Icingadb\Forms\RedisConfigForm;
use Icinga\Web\Form;

class RedisPage extends Form
{
    public function init()
    {
        $this->setName('setup_icingadb_redis');
        $this->setTitle(t('Icinga DB Redis'));
        $this->addDescription(t(
            'Please fill out the connection details to access the Icinga DB Redis.'
        ));
        $this->setValidatePartial(true);
    }

    public function createElements(array $formData)
    {
        $redisConfigForm = new RedisConfigForm();
        $redisConfigForm->createElements($formData);
        $this->addElements($redisConfigForm->getElements());
        $this->addDisplayGroups($redisConfigForm->getDisplayGroups());
    }

    public function isValid($formData)
    {
        if (! parent::isValid($formData)) {
            return false;
        }

        if (($el = $this->getElement('skip_validation')) === null || ! $el->isChecked()) {
            if (! RedisConfigForm::checkRedis($this)) {
                if ($el === null) {
                    RedisConfigForm::addSkipValidationCheckbox($this);
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

        if (isset($formData['backend_validation'])) {
            return RedisConfigForm::checkRedis($this);
        }

        return true;
    }
}
