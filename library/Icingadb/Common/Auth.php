<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Application\Logger;
use Icinga\Authentication\Auth as IcingaAuth;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Icingadb\Authentication\ObjectAuthorization;
use Icinga\Security\SecurityException;
use Icinga\Util\StringHelper;
use ipl\Orm\Compat\FilterProcessor;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Orm\UnionQuery;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;
use ipl\Web\Filter\Renderer;

trait Auth
{
    public function getAuth(): IcingaAuth
    {
        return IcingaAuth::getInstance();
    }

    /**
     * Check whether access to the given route is permitted
     *
     * @param string $name
     *
     * @return bool
     */
    public function isPermittedRoute(string $name): bool
    {
        if ($this->getAuth()->getUser()->isUnrestricted()) {
            return true;
        }

        // The empty array is for PHP pre 7.4, older versions require at least a single param for array_merge
        $routeDenylist = array_flip(array_merge([], ...array_map(function ($restriction) {
            return StringHelper::trimSplit($restriction);
        }, $this->getAuth()->getRestrictions('icingadb/denylist/routes'))));

        if (! array_key_exists($name, $routeDenylist)) {
            return true;
        }

        return false;
    }

    /**
     * Check whether the permission is granted on the object
     *
     * @param string $permission
     * @param Model $object
     *
     * @return bool
     */
    public function isGrantedOn(string $permission, Model $object): bool
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
    public function isGrantedOnType(string $permission, string $type, Filter\Rule $filter, bool $cache = true): bool
    {
        if ($this->getAuth()->getUser()->isUnrestricted()) {
            return $this->getAuth()->hasPermission($permission);
        }

        return ObjectAuthorization::grantsOnType($permission, $type, $filter, $cache);
    }

    /**
     * Check whether the filter matches the given object
     *
     * @param string $queryString
     * @param Model  $object
     *
     * @return bool
     */
    public function isMatchedOn(string $queryString, Model $object): bool
    {
        return ObjectAuthorization::matchesOn($queryString, $object);
    }

    /**
     * Assert that the given filter does not contain any column restrictions
     *
     * @param Filter\Rule $filter The filter to check
     *
     * @return void
     *
     * @throws SecurityException
     */
    public function assertColumnRestrictions(Filter\Rule $filter): void
    {
        if ($this->getAuth()->getUser() === null || $this->getAuth()->getUser()->isUnrestricted()) {
            return;
        }

        $forbiddenVars = Filter::none();
        foreach ($this->getAuth()->getUser()->getRoles() as $role) {
            if (($restriction = $role->getRestrictions('icingadb/denylist/variables'))) {
                foreach (explode(',', $restriction) as $value) {
                    $forbiddenVars->add(Filter::like('name', trim($value))->ignoreCase());
                }
            }

            if (($restriction = $role->getRestrictions('icingadb/protect/variables'))) {
                foreach (explode(',', $restriction) as $value) {
                    $forbiddenVars->add(Filter::like('name', trim($value))->ignoreCase());
                }
            }
        }

        if ($forbiddenVars->isEmpty()) {
            return;
        }

        $checkVars = static function (Filter\Rule $filter) use ($forbiddenVars, &$checkVars) {
            if ($filter instanceof Filter\Chain) {
                foreach ($filter as $rule) {
                    $checkVars($rule);
                }
            } elseif (
                ! $filter->metaData()->get('_isRestriction', false)
                && preg_match('/^(?:host|service)\.vars\.(.*)/i', $filter->getColumn(), $matches)
            ) {
                if (! Filter::match($forbiddenVars, ['name' => $matches[1]])) {
                    throw new SecurityException(
                        'The variable "%s" is not allowed to be queried.',
                        $matches[1]
                    );
                }
            }
        };

        $checkVars($filter);
    }

    /**
     * Apply Icinga DB Web's restrictions depending on what is queried
     *
     * This will apply `icingadb/filter/objects` in any case. `icingadb/filter/services` is only
     * applied to queries fetching services and `icingadb/filter/hosts` is applied to queries
     * fetching either hosts or services. It also applies custom variable restrictions and
     * obfuscations. (`icingadb/denylist/variables` and `icingadb/protect/variables`)
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
            $forceGroupFilterOptimization = true;
        } else {
            $queries = [$query];
            $forceGroupFilterOptimization = false;
        }

        foreach ($queries as $query) {
            $relations = [$query->getModel()->getTableName()];
            foreach ($query->getWith() as $relationPath => $relation) {
                $relations[$relationPath] = $relation->getTarget()->getTableName();
            }

            $customVarRelationName = array_search('customvar_flat', $relations, true);
            $applyServiceRestriction = $relations[0] === 'dependency_node' || in_array('service', $relations, true);
            $applyHostRestriction = $relations[0] === 'dependency_node' || in_array('host', $relations, true)
                // Hosts and services have a special relation as a service can't exist without its host.
                // Hence why the hosts restriction is also applied if only services are queried.
                || $applyServiceRestriction;
            // Redundancy groups have no relation to anything in order to be subject
            // for authorization, so they must be exempt from the respective filters.
            $skipRedundancyGroups = $relations[0] === 'dependency_node';

            $hostStateRelation = array_search('host_state', $relations, true);
            $serviceStateRelation = array_search('service_state', $relations, true);

            $resolver = $query->getResolver();

            $queryFilter = Filter::any();
            $forbiddenVars = Filter::none();
            $obfuscationRules = Filter::all();
            foreach ($this->getAuth()->getUser()->getRoles() as $role) {
                $roleFilter = Filter::all();

                if ($customVarRelationName !== false) {
                    if (($restriction = $role->getRestrictions('icingadb/denylist/variables'))) {
                        $this->flattenSemanticallyEqualRules($forbiddenVars, $this->parseDenylist(
                            $restriction,
                            $customVarRelationName
                                ? $resolver->qualifyColumn('flatname', $customVarRelationName)
                                : 'flatname'
                        ));
                    }

                    if (($restriction = $role->getRestrictions('icingadb/protect/variables'))) {
                        $obfuscationRules->add($this->parseDenylist(
                            $restriction,
                            $customVarRelationName
                                ? $resolver->qualifyColumn('flatname', $customVarRelationName)
                                : 'flatname'
                        ));
                    }
                }

                if ($customVarRelationName === false || count($relations) > 1) {
                    if (($restriction = $role->getRestrictions('icingadb/filter/objects'))) {
                        if ($skipRedundancyGroups) {
                            $roleFilter->add(Filter::any(
                                Filter::all(
                                    Filter::unlike('host_id', '*'),
                                    Filter::unlike('service_id', '*')
                                ),
                                $this->parseRestriction($restriction, 'icingadb/filter/objects')
                            ));
                        } else {
                            $roleFilter->add($this->parseRestriction($restriction, 'icingadb/filter/objects'));
                        }
                    }

                    if ($applyHostRestriction && ($restriction = $role->getRestrictions('icingadb/filter/hosts'))) {
                        $hostFilter = $this->parseRestriction($restriction, 'icingadb/filter/hosts');
                        if ($forceGroupFilterOptimization) {
                            $this->forceQueryOptimization($hostFilter, 'hostgroup.name');
                        }

                        if ($skipRedundancyGroups) {
                            $roleFilter->add(Filter::any(Filter::unlike('host_id', '*'), $hostFilter));
                        } else {
                            $roleFilter->add($hostFilter);
                        }
                    }

                    if (
                        $applyServiceRestriction
                        && ($restriction = $role->getRestrictions('icingadb/filter/services'))
                    ) {
                        $serviceFilter = $this->parseRestriction($restriction, 'icingadb/filter/services');
                        if ($forceGroupFilterOptimization) {
                            $this->forceQueryOptimization($serviceFilter, 'servicegroup.name');
                        }

                        $roleFilter->add(Filter::any(Filter::unlike('service.id', '*'), $serviceFilter));
                    }
                }

                if (! $roleFilter->isEmpty()) {
                    if (Logger::getInstance()->getLevel() === Logger::DEBUG) {
                        Logger::debug(
                            'Preparing restrictions for user %s with role %s: %s; Current query filter: %s',
                            $this->getAuth()->getUser()->getUsername(),
                            $role->getName(),
                            (new Renderer($roleFilter))->setStrict()->render(),
                            (new Renderer($queryFilter))->setStrict()->render()
                        );
                    }

                    if (! $this->flattenSemanticallyEqualRules($queryFilter, $roleFilter)) {
                        $queryFilter->add($roleFilter);
                    }
                }
            }

            if (! $this->getAuth()->hasPermission('icingadb/object/show-source')) {
                // In case the user does not have permission to see the object's `Source` tab, then the user must be
                // restricted from accessing the executed command for the object.
                $columns = $query->getColumns();
                $commandColumns = [];
                if ($hostStateRelation !== false) {
                    $commandColumns[] = $resolver->qualifyColumn('check_commandline', $hostStateRelation);
                }

                if ($serviceStateRelation !== false) {
                    $commandColumns[] = $resolver->qualifyColumn('check_commandline', $serviceStateRelation);
                }

                if (! empty($columns)) {
                    foreach ($commandColumns as $commandColumn) {
                        $commandColumnPath = array_search($commandColumn, $columns, true);
                        if ($commandColumnPath !== false) {
                            $columns[$commandColumn] = new Expression("'***'");
                            unset($columns[$commandColumnPath]);
                        }
                    }

                    $query->columns($columns);
                } else {
                    $query->withoutColumns($commandColumns);
                }
            }

            if (! $obfuscationRules->isEmpty()) {
                $flatvaluePath = $customVarRelationName
                    ? $resolver->qualifyColumn('flatvalue', $customVarRelationName)
                    : 'flatvalue';

                $columns = $query->getColumns();
                if (empty($columns)) {
                    $columns = [
                        $customVarRelationName
                            ? $resolver->qualifyColumn('flatname', $customVarRelationName)
                            : 'flatname',
                        $flatvaluePath
                    ];
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
                    $rendered = $query->getDb()->getQueryBuilder()->buildCondition($where, $values);
                    $columns[$flatvaluePath] = new Expression(
                        "CASE WHEN (" . $rendered . ") THEN (%s) ELSE '***' END",
                        [$flatvalue],
                        ...$values
                    );

                    $query->columns($columns);
                }
            }

            if (Logger::getInstance()->getLevel() === Logger::DEBUG) {
                Logger::debug(
                    'Final restrictions for user %s: %s',
                    $this->getAuth()->getUser()->getUsername(),
                    (new Renderer($queryFilter))->setStrict()->render()
                );
            }

            $query->filter($queryFilter)
                ->filter($forbiddenVars);
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
    protected function parseRestriction(string $queryString, string $restriction): Filter\Rule
    {
        $allowedColumns = [
            'host.name',
            'hostgroup.name',
            'host.user.name',
            'host.usergroup.name',
            'service.name',
            'servicegroup.name',
            'service.user.name',
            'service.usergroup.name',
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
                                $condition->metaData()->set('_isRestriction', true);

                                return;
                            }
                        } elseif ($column === $condition->getColumn()) {
                            $condition->metaData()->set('_isRestriction', true);

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
     * Parse the given denylist
     *
     * @param string $denylist Comma separated list of column names
     * @param string $column The column which should not equal any of the denylisted names
     *
     * @return Filter\None
     */
    protected function parseDenylist(string $denylist, string $column): Filter\None
    {
        $filter = Filter::none();
        foreach (explode(',', $denylist) as $value) {
            $filter->add(Filter::like($column, trim($value)));
        }

        return $filter;
    }

    /**
     * Flatten the given rule into the given chain if they are semantically equal
     *
     * This is needed as the same filter may be added multiple times to the same query, which leads to multiple nested
     * chains of the same type. This method flattens those chains into a single one, which allows for better
     * optimization and therefore enhanced efficiency.
     *
     * @param Filter\Chain $to
     * @param Filter\Chain $from
     *
     * @return bool Whether the $from rule has been added to the $to chain
     */
    private function flattenSemanticallyEqualRules(Filter\Chain $to, Filter\Chain $from): bool
    {
        $transfer = $from instanceof $to || (! $from instanceof Filter\None && $from->count() === 1);
        foreach (iterator_to_array($from) as $rule) {
            if ($rule instanceof Filter\Chain) {
                if ($transfer) {
                    if (! $this->flattenSemanticallyEqualRules($to, $rule)) {
                        $to->add($rule);
                    }

                    $from->remove($rule);
                } elseif ($this->flattenSemanticallyEqualRules($from, $rule)) {
                    $from->remove($rule);
                }
            } elseif ($transfer) {
                $to->add($rule);
                $from->remove($rule);
            }
        }

        if (! $to->has($from)) {
            if (! $from->isEmpty()) {
                $to->add($from);
            }

            return true;
        } elseif ($from->isEmpty()) {
            $to->remove($from);
        }

        return false;
    }

    /**
     * Force query optimization on the given service/host filter rule
     *
     * Applies forceOptimization, when the given filter rule contains the given filter column
     *
     * @param Filter\Rule $filterRule
     * @param string $filterColumn
     *
     * @return void
     */
    protected function forceQueryOptimization(Filter\Rule $filterRule, string $filterColumn)
    {
        // TODO: This is really a very poor solution is therefore only a quick fix.
        //  We need to somehow manage to make this more enjoyable and creative!
        if ($filterRule instanceof Filter\Chain) {
            foreach ($filterRule as $rule) {
                $this->forceQueryOptimization($rule, $filterColumn);
            }
        } elseif ($filterRule->getColumn() === $filterColumn) {
            $filterRule->metaData()->set('forceOptimization', true);
        }
    }
}
