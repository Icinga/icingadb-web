<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Exception\ConfigurationError;
use Icinga\Module\Icingadb\Authentication\ObjectAuthorization;
use ipl\Orm\Compat\FilterProcessor;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Orm\UnionQuery;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;

trait Auth
{
    public function getAuth()
    {
        return \Icinga\Authentication\Auth::getInstance();
    }

    /**
     * Check whether the permission is granted on the object
     *
     * @param string $permission
     * @param Model $object
     *
     * @return bool
     */
    public function isGrantedOn($permission, Model $object)
    {
        if ($this->getAuth()->getUser()->isUnrestricted()) {
            return $this->getAuth()->hasPermission($permission);
        }

        return ObjectAuthorization::grantsOn($permission, $object);
    }

    /**
     * Check whether the permission is granted on objects matching the type and filter
     *
     * The check will be performed on every object matching the filter. Though the result
     * only allows to determine whether the permission is granted on **any** or *none*
     * of the objects in question. Any subsequent call to {@see Auth::isGrantedOn} will
     * make use of the underlying results the check has determined in order to avoid
     * unnecessary queries.
     *
     * @param string $permission
     * @param string $type
     * @param Filter\Rule $filter
     * @param bool $cache Pass `false` to not perform the check on every object
     *
     * @return bool
     */
    public function isGrantedOnType($permission, $type, Filter\Rule $filter, $cache = true)
    {
        if ($this->getAuth()->getUser()->isUnrestricted()) {
            return $this->getAuth()->hasPermission($permission);
        }

        return ObjectAuthorization::grantsOnType($permission, $type, $filter, $cache);
    }

    /**
     * Apply Icinga DB Web's restrictions depending on what is queried
     *
     * This will apply `icingadb/filter/objects` in any case. `icingadb/filter/services` is only
     * applied to queries fetching services and `icingadb/filter/hosts` is applied to queries
     * fetching either hosts or services. It also applies custom variable restrictions and
     * obfuscations. (`icingadb/blacklist/variables` and `icingadb/protect/variables`)
     *
     * @param Query $query
     *
     * @return void
     */
    public function applyRestrictions(Query $query)
    {
        if ($this->getAuth()->getUser()->isUnrestricted()) {
            return;
        }

        if ($query instanceof UnionQuery) {
            $queries = $query->getUnions();
        } else {
            $queries = [$query];
        }

        foreach ($queries as $query) {
            $relations = [$query->getModel()->getTableName()];
            foreach ($query->getWith() as $relationPath => $relation) {
                $relations[$relationPath] = $relation->getTarget()->getTableName();
            }

            $customVarRelationName = array_search('customvar_flat', $relations, true);
            $applyServiceRestriction = in_array('service', $relations, true);
            $applyHostRestriction = in_array('host', $relations, true)
                // Hosts and services have a special relation as a service can't exist without its host.
                // Hence why the hosts restriction is also applied if only services are queried.
                || $applyServiceRestriction;

            $resolver = $query->getResolver();

            $queryFilter = Filter::any();
            $obfuscationRules = Filter::any();
            foreach ($this->getAuth()->getUser()->getRoles() as $role) {
                $roleFilter = Filter::all();

                if ($customVarRelationName !== false) {
                    if (($restriction = $role->getRestrictions('icingadb/blacklist/variables'))) {
                        $roleFilter->add($this->parseBlacklist(
                            $restriction,
                            $customVarRelationName
                                ? $resolver->qualifyColumn('flatname', $customVarRelationName)
                                : 'flatname'
                        ));
                    }

                    if (($restriction = $role->getRestrictions('icingadb/protect/variables'))) {
                        $obfuscationRules->add($this->parseBlacklist(
                            $restriction,
                            $customVarRelationName
                                ? $resolver->qualifyColumn('flatname', $customVarRelationName)
                                : 'flatname'
                        ));
                    }
                }

                if ($customVarRelationName === false || count($relations) > 1) {
                    if (($restriction = $role->getRestrictions('icingadb/filter/objects'))) {
                        $roleFilter->add($this->parseRestriction($restriction, 'icingadb/filter/objects'));
                    }

                    if ($applyHostRestriction && ($restriction = $role->getRestrictions('icingadb/filter/hosts'))) {
                        $roleFilter->add($this->parseRestriction($restriction, 'icingadb/filter/hosts'));
                    }

                    if (
                        $applyServiceRestriction
                        && ($restriction = $role->getRestrictions('icingadb/filter/services'))
                    ) {
                        $roleFilter->add(
                            Filter::any(
                                Filter::unequal('service.id', '*'),
                                $this->parseRestriction($restriction, 'icingadb/filter/services')
                            )
                        );
                    }
                }

                if (! $roleFilter->isEmpty()) {
                    $queryFilter->add($roleFilter);
                }
            }

            if (! $obfuscationRules->isEmpty()) {
                $flatvaluePath = $customVarRelationName
                    ? $resolver->qualifyColumn('flatvalue', $customVarRelationName)
                    : 'flatvalue';

                $columns = $query->getColumns();
                if (empty($columns)) {
                    $columns = [$flatvaluePath, '*'];
                }

                $flatvalue = null;
                if (isset($columns[$flatvaluePath])) {
                    $flatvalue = $columns[$flatvaluePath];
                } else {
                    $flatvaluePathAt = array_search($flatvaluePath, $columns, true);
                    if ($flatvaluePathAt !== false) {
                        $flatvalue = $columns[$flatvaluePathAt];
                        if (is_int($flatvaluePathAt)) {
                            unset($columns[$flatvaluePathAt]);
                        } else {
                            $flatvaluePath = $flatvaluePathAt;
                        }
                    }
                }

                if ($flatvalue !== null) {
                    // TODO: The four lines below are needed because there is still no way to postpone filter column
                    //       qualification. (i.e. Just like the expression, filter rules need to be handled the same
                    //       so that their columns are qualified lazily when assembling the query)
                    $queryClone = clone $query;
                    $queryClone->getSelectBase()->resetWhere();
                    FilterProcessor::apply($obfuscationRules, $queryClone);
                    $where = $queryClone->getSelectBase()->getWhere();

                    $values = [];
                    $rendered = $this->getDb()->getQueryBuilder()->buildCondition($where, $values);
                    $columns[$flatvaluePath] = new Expression(
                        "CASE WHEN (" . $rendered . ") THEN (%s) ELSE '***' END",
                        [$flatvalue],
                        ...$values
                    );

                    $query->setColumns($columns);
                }
            }

            $query->filter($queryFilter);
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

    /**
     * Parse the given blacklist
     *
     * @param string $blacklist Comma separated list of column names
     * @param string $column The column which should not equal any of the blacklisted names
     *
     * @return Filter\None
     */
    protected function parseBlacklist($blacklist, $column)
    {
        $filter = Filter::none();
        foreach (explode(',', $blacklist) as $value) {
            $filter->add(Filter::equal($column, trim($value)));
        }

        return $filter;
    }
}
