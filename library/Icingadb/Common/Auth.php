<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Exception\ConfigurationError;
use ipl\Orm\Compat\FilterProcessor;
use ipl\Orm\Query;
use ipl\Orm\UnionQuery;
use ipl\Sql\Filter\IsNull;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;

trait Auth
{
    public function getAuth()
    {
        return \Icinga\Authentication\Auth::getInstance();
    }

    /**
     * Apply Icinga DB Web's restrictions depending on what is queried
     *
     * This will apply `icingadb/filter/objects` in any case. `icingadb/filter/services` is only
     * applied to queries fetching services and `icingadb/filter/hosts` is applied to queries
     * fetching either hosts or services.
     *
     * @param Query $query
     *
     * @return void
     */
    public function applyRestrictions(Query $query)
    {
        if ($query instanceof UnionQuery) {
            $queries = $query->getUnions();
        } else {
            $queries = [$query];
        }

        foreach ($queries as $query) {
            $relations = [$query->getModel()->getTableName()];
            foreach ($query->getWith() as $relation) {
                $relations[] = $relation->getTarget()->getTableName();
            }

            $applyServiceRestriction = in_array('service', $relations, true);
            $applyHostRestriction = in_array('host', $relations, true)
                // Hosts and services have a special relation as a service can't exist without its host.
                // Hence why the hosts restriction is also applied if only services are queried.
                || $applyServiceRestriction;

            $queryFilter = Filter::any();
            foreach ($this->getAuth()->getUser()->getRoles() as $role) {
                $roleFilter = Filter::all();

                if (($restriction = $role->getRestrictions('icingadb/filter/objects'))) {
                    $roleFilter->add($this->parseRestriction($restriction, 'icingadb/filter/objects'));
                }

                if ($applyHostRestriction && ($restriction = $role->getRestrictions('icingadb/filter/hosts'))) {
                    $roleFilter->add($this->parseRestriction($restriction, 'icingadb/filter/hosts'));
                }

                if ($applyServiceRestriction && ($restriction = $role->getRestrictions('icingadb/filter/services'))) {
                    $roleFilter->add(
                        Filter::any(
                            new IsNull('service.id'),
                            $this->parseRestriction($restriction, 'icingadb/filter/services')
                        )
                    );
                }

                // TODO: Should we allow full access if there's a role that doesn't define any restriction?
                $queryFilter->add($roleFilter);
            }

            FilterProcessor::apply($queryFilter, $query);
        }
    }

    /**
     * Parse the given restriction
     *
     * @param string $queryString
     * @param string $restriction The name of the restriction
     *
     * @return Filter\Rule
     */
    protected function parseRestriction($queryString, $restriction)
    {
        $allowedColumns = [
            'host.name',
            'hostgroup.name',
            'service.name',
            'servicegroup.name',
            '(host|service).vars.<customvar-name>' => function ($c) {
                return preg_match('/^(?:host|service)\.vars\./i', $c);
            }
        ];

        return QueryString::fromString($queryString)
            ->on(
                QueryString::ON_CONDITION,
                function (Filter\Condition $condition) use (
                    $restriction,
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
                        t(
                            'Cannot apply restriction %s using the filter %s.'
                            . ' You can only use the following columns: %s'
                        ),
                        $restriction,
                        $queryString,
                        join(
                            ', ',
                            array_map(
                                function ($k, $v) {
                                    return is_string($k) ? $k : $v;
                                },
                                array_keys($allowedColumns),
                                $allowedColumns
                            )
                        )
                    );
                }
            )->parse();
    }
}
