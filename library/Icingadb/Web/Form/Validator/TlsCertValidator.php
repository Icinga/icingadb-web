<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Form\Validator;

use Zend_Validate_Abstract;

class TlsCertValidator extends Zend_Validate_Abstract
{
    use PemSanitizer;

    /**
     * Tells whether $value is a valid PEM-encoded TLS certificate.
     *
     * @param   string  $value
     *
     * @return  bool
     */
    public function isValid($value)
    {
        if (openssl_x509_parse($this->sanitizePem($value)) === false) {
            $this->_error('BAD_CERT');

            return false;
        }

        return true;
    }

    protected function _error($messageKey, $value = null)
    {
        if ($messageKey === 'BAD_CERT') {
            $this->_messages[$messageKey] = t('The input must be a valid PEM-encoded TLS certificate.');
        } else {
            parent::_error($messageKey, $value);
        }
    }
}
