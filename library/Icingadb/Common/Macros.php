<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Application\Logger;
use Icinga\Module\Icingadb\Compat\CompatHost;
use Icinga\Module\Icingadb\Compat\CompatService;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Orm\Query;
use ipl\Orm\ResultSet;

use function ipl\Stdlib\get_php_type;

trait Macros
{
    /**
     * Get the given string with macros being resolved
     *
     * @param string $input  The string in which to look for macros
     * @param Host|Service|CompatService|CompatHost $object The host or service used to resolve the macros
     *
     * @return string
     */
    public function expandMacros(string $input, $object): string
    {
        if (preg_match_all('@\$([^\$\s]+)\$@', $input, $matches)) {
            foreach ($matches[1] as $key => $value) {
                $newValue = $this->resolveMacro($value, $object);
                if ($newValue !== $value) {
                    $input = str_replace($matches[0][$key], $newValue, $input);
                }
            }
        }

        return $input;
    }

    /**
     * Resolve a macro based on the given object
     *
     * @param string $macro  The macro to resolve
     * @param Host|Service|CompatService|CompatHost  $object The host or service used to resolve the macros
     *
     * @return string
     */
    public function resolveMacro(string $macro, $object): string
    {
        if ($object instanceof Host || (property_exists($object, 'type') && $object->type === 'host')) {
            $objectType = 'host';
        } else {
            $objectType = 'service';
        }

        $path = null;
        $macroType = $objectType;
        $isCustomVar = false;
        if (preg_match('/^((host|service)\.)?vars\.(.+)/', $macro, $matches)) {
            if (! empty($matches[2])) {
                $macroType = $matches[2];
            }

            $path = $matches[3];
            $isCustomVar = true;
        } elseif (preg_match('/^(\w+)\.(.+)/', $macro, $matches)) {
            $macroType = $matches[1];
            $path = $matches[2];
        }

        try {
            if ($path !== null) {
                if ($macroType !== $objectType) {
                    $value = $object->$macroType;
                } else {
                    $value = $object;
                }

                $properties = explode('.', $path);

                do {
                    $column = array_shift($properties);
                    if ($value instanceof Query || $value instanceof ResultSet || is_array($value)) {
                        Logger::debug(
                            'Failed to resolve property "%s" on a "%s" type.',
                            $isCustomVar ? 'vars' : $column,
                            get_php_type($value)
                        );
                        $value = null;
                        break;
                    }

                    if ($isCustomVar) {
                        $value = $value->vars[$path];
                        break;
                    }

                    $value = $value->$column;
                } while (! empty($properties) && $value !== null);
            } else {
                $value = $object->$macro;
            }
        } catch (\Exception $e) {
            $objectName = $object->name;
            if ($objectType === 'service' && isset($object->host)) {
                $objectName = $object->host->name . '!' . $objectName;
            }

            $value = null;
            Logger::debug('Unable to resolve macro "%s" on object "%s". An error occured: %s', $macro, $objectName, $e);
        }

        if ($value instanceof Query || $value instanceof ResultSet || is_array($value)) {
            Logger::debug(
                'It is not allowed to use "%s" as a macro which produces a "%s" type as a result.',
                $macro,
                get_php_type($value)
            );
            $value = null;
        }

        return $value !== null ? $value : $macro;
    }
}
