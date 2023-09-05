<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Controller;

use Zend_Controller_Router_Route_Regex;

/**
 * RegexRouter with support for regex named groups
 *
 * This works the same as the standard regex router by Zend.
 * The only, albeit small, difference is that it supports named regular expression groups.
 * Allowing to initialize the module, controller and action dynamically.
 */
class RegexRouter extends Zend_Controller_Router_Route_Regex
{
    public function match($path, $_ = false)
    {
        $path = trim(urldecode($path), self::URI_DELIMITER);
        $pattern = '#^' . $this->_regex . '$#i';

        $res = preg_match($pattern, $path, $values);
        if (! $res) {
            return false;
        }

        // $values are not filtered further here, providing support for named groups
        unset($values[0]);

        $this->_values = $values;

        $values   = $this->_getMappedValues($values);
        $defaults = $this->_getMappedValues($this->_defaults, false, true);

        return $values + $defaults;
    }
}
