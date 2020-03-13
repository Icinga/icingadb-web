<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Compat;

use Icinga\Authentication\Auth;
use Icinga\Data\Filter\Filter;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\QueryException;

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
     * @return  Filter              Filter object
     * @throws  ConfigurationError  If the restriction contains invalid filter columns
     */
    public static function getRestriction($name)
    {
        $restriction = Filter::matchAny();
        $restriction->setAllowedFilterColumns(array(
            'host_name',
            'hostgroup_name',
            'instance_name',
            'service_description',
            'servicegroup_name',
            function ($c) {
                return preg_match('/^_(?:host|service)_/i', $c);
            }
        ));

        foreach (self::getRestrictions($name) as $filter) {
            if ($filter === '*') {
                return Filter::matchAll();
            }

            try {
                $restriction->addFilter(Filter::fromQueryString($filter));
            } catch (QueryException $e) {
                throw new ConfigurationError(
                    mt(
                        'monitoring',
                        'Cannot apply restriction %s using the filter %s. You can only use the following columns: %s'
                    ),
                    $name,
                    $filter,
                    implode(', ', array(
                        'instance_name',
                        'host_name',
                        'hostgroup_name',
                        'service_description',
                        'servicegroup_name',
                        '_(host|service)_<customvar-name>'
                    )),
                    $e
                );
            }
        }

        if ($restriction->isEmpty()) {
            return Filter::matchAll();
        }

        $restriction->setAllowedFilterColumns([]);
        return $restriction;
    }
}
