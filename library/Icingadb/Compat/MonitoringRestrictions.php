<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Compat;

use Icinga\Authentication\Auth;
use Icinga\Exception\ConfigurationError;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;

class MonitoringRestrictions
{
    /**
     * Return restriction information for an eventually authenticated user
     *
     * @param   string  $name   Restriction name
     *
     * @return  array
     */
    protected static function getRestrictions($name)
    {
        return Auth::getInstance()->getRestrictions($name);
    }

    /**
     * Get a restriction of the authenticated user
     *
     * @param   string $name        Name of the restriction
     *
     * @return  Filter\Rule         Filter rule
     * @throws  ConfigurationError  If the restriction contains invalid filter columns
     */
    public static function getRestriction($name)
    {
        $restriction = Filter::any();

        $allowedColumns = [
            'host_name',
            'hostgroup_name',
            'instance_name',
            'service_description',
            'servicegroup_name',
            '_(host|service)_<customvar-name>' => function ($c) {
                return preg_match('/^_(?:host|service)_/i', $c);
            }
        ];

        foreach (self::getRestrictions($name) as $queryString) {
            if ($queryString === '*') {
                return Filter::all();
            }

            $restriction->add(QueryString::fromString($queryString)
                ->on(QueryString::ON_CONDITION, function (Filter\Condition $condition) use (
                    $name,
                    $queryString,
                    $allowedColumns
                ) {
                    foreach ($allowedColumns as $column) {
                        if (is_callable($column)) {
                            if ($column($condition->getColumn())) {
                                return;
                            }
                        } elseif ($column === $condition->getColumn()) {
                            return;
                        }
                    }

                    throw new ConfigurationError(
                        t('Cannot apply restriction %s using the filter %s.'
                          . ' You can only use the following columns: %s'),
                        $name,
                        $queryString,
                        join(', ', array_map(function ($k, $v) {
                            return is_string($k) ? $k : $v;
                        }, array_keys($allowedColumns), $allowedColumns))
                    );
                })->parse());
        }

        return $restriction->isEmpty() ? Filter::all() : $restriction;
    }
}
