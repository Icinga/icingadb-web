<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Form\Validator;

use Zend_Validate_Abstract;

class TlsKeyValidator extends Zend_Validate_Abstract
{
    use PemSanitizer;

    /**
     * Tells whether $value is a valid PEM-encoded TLS private key.
     *
     * @param   string  $value
     *
     * @return  bool
     */
    public function isValid($value)
    {
        if (openssl_pkey_get_private($this->sanitizePem($value)) === false) {
            $this->_error('BAD_KEY');

            return false;
        }

        return true;
    }

    protected function _error($messageKey, $value = null)
    {
        if ($messageKey === 'BAD_KEY') {
            $this->_messages[$messageKey] = t('The input must be a valid PEM-encoded TLS private key.');
        } else {
            parent::_error($messageKey, $value);
        }
    }
}
