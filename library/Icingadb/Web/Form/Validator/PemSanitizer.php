<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Form\Validator;

trait PemSanitizer
{
    /**
     * Sanitizes the user input $pem, filtering file://... out.
     *
     * Rationale:
     * If file://... is passed to functions like
     * openssl_x509_parse() and openssl_pkey_get_private(), they open the specified file.
     * This shall not happen due to arbitrary user input and such input shall be considered invalid.
     *
     * @param   string  $pem
     *
     * @return  string
     */
    private function sanitizePem($pem)
    {
        return preg_match('/\A\w+:/', $pem) ? '' : $pem;
    }
}
