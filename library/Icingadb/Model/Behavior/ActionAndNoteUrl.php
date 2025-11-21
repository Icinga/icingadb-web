<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Model\Behavior;

use ipl\Orm\Contract\PropertyBehavior;

class ActionAndNoteUrl extends PropertyBehavior
{
    public function fromDb($value, $key, $_)
    {
        if (empty($value)) {
            return [];
        }

        $links = [];
        if (strpos($value, "' ") === false) {
            $links[] = $value;
        } else {
            foreach (explode("' ", $value) as $url) {
                $url = strpos($url, "'") === 0 ? substr($url, 1) : $url;
                $url = strpos($url, "'") === strlen($url) - 1 ? substr($url, 0, strlen($url) - 1) : $url;
                $links[] = $url;
            }
        }

        return $links;
    }

    public function toDb($value, $key, $_)
    {
        if (empty($value) || ! is_array($value)) {
            return $value;
        }

        if (count($value) === 1) {
            return $value[0];
        }

        $links = '';
        foreach ($value as $url) {
            if (! empty($links)) {
                $links .= ' ';
            }

            $links .= "'$url'";
        }

        return $links;
    }
}
