<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Authentication;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use InvalidArgumentException;
use ipl\Orm\Compat\FilterProcessor;
use ipl\Orm\Model;
use ipl\Sql\Expression;
use ipl\Sql\Select;
use ipl\Stdlib\Filter;

class ObjectAuthorization
{
    use Auth;
    use Database;

    /** @var array */
    protected static $knownGrants = [];

    /**
     * Check whether the permission is granted on the object
     *
     * @param string $permission
     * @param Model $for The object
     *
     * @return bool
     */
    public static function grantsOn($permission, Model $for)
    {
        $self = new static();

        $tableName = $for->getTableName();
        $uniqueId = $for->{$for->getKeyName()};
        if (! isset(self::$knownGrants[$tableName][$uniqueId])) {
            $self->loadGrants(
                get_class($for),
                Filter::equal($for->getKeyName(), $uniqueId)
            );
        }

        return $self->checkGrants($permission, self::$knownGrants[$tableName][$uniqueId]);
    }

    /**
     * Check whether the permission is granted on objects matching the type and filter
     *
     * The check will be performed on every object matching the filter. Though the result
     * only allows to determine whether the permission is granted on **any** or *none*
     * of the objects in question. Any subsequent call to {@see ObjectAuthorization::grantsOn}
     * will make use of the underlying results the check has determined in order to avoid
     * unnecessary queries.
     *
     * @param string $permission
     * @param string $type
     * @param Filter\Rule $filter
     * @param bool $cache Pass `false` to not perform the check on every object
     *
     * @return bool
     */
    public static function grantsOnType($permission, $type, Filter\Rule $filter, $cache = false)
    {
        switch ($type) {
            case 'host':
                $for = Host::class;
                break;
            case 'service':
                $for = Service::class;
                break;
            default:
                throw new InvalidArgumentException(sprintf('Unknown type "%s"', $type));
        }

        $self = new static();

        $uniqueId = spl_object_hash($filter);
        if (! isset(self::$knownGrants[$type][$uniqueId])) {
            $self->loadGrants($for, $filter, $cache);
        }

        return $self->checkGrants($permission, self::$knownGrants[$type][$uniqueId]);
    }

    /**
     * Load all the user's roles that grant access to at least one object matching the filter
     *
     * @param string $model The class path to the object model
     * @param Filter\Rule $filter
     * @param bool $cache Pass `false` to not populate the cache with the matching objects
     *
     * @return void
     */
    protected function loadGrants($model, Filter\Rule $filter, $cache = true)
    {
        /** @var Model $model */
        $query = $model::on($this->getDb());
        $tableName = $query->getModel()->getTableName();

        $roleExpressions = [];
        $rolesWithoutRestrictions = [];
        foreach ($this->getAuth()->getUser()->getRoles() as $role) {
            $roleFilter = Filter::all();
            if (($restriction = $role->getRestrictions('icingadb/filter/objects'))) {
                $roleFilter->add($this->parseRestriction($restriction, 'icingadb/filter/objects'));
            }

            if ($tableName === 'host' || $tableName === 'service') {
                if (($restriction = $role->getRestrictions('icingadb/filter/hosts'))) {
                    $roleFilter->add($this->parseRestriction($restriction, 'icingadb/filter/hosts'));
                }
            }

            if ($tableName === 'service' && ($restriction = $role->getRestrictions('icingadb/filter/services'))) {
                $roleFilter->add($this->parseRestriction($restriction, 'icingadb/filter/services'));
            }

            if ($roleFilter->isEmpty()) {
                $rolesWithoutRestrictions[] = $role->getName();
                continue;
            }

            if ($cache) {
                FilterProcessor::apply($roleFilter, $query);
                $where = $query->getSelectBase()->getWhere();
                $query->getSelectBase()->resetWhere();

                $values = [];
                $rendered = $this->getDb()->getQueryBuilder()->buildCondition($where, $values);
                $roleExpressions[$role->getName()] = new Expression($rendered, null, ...$values);
            } else {
                $roleExpressions[$role->getName()] = (clone $query)
                    ->columns([new Expression('1')])
                    ->filter($roleFilter)
                    ->filter($filter)
                    ->limit(1)
                    ->assembleSelect()
                    ->resetOrderBy();
            }
        }

        $rolesWithRestrictions = [];
        if (! empty($roleExpressions)) {
            if ($cache) {
                $query->columns('id')->columns($roleExpressions);
                $query->filter($filter);
            } else {
                $query = [$this->getDb()->fetchOne((new Select())->columns($roleExpressions))];
            }

            foreach ($query as $row) {
                $roles = $rolesWithoutRestrictions;
                foreach ($roleExpressions as $alias => $_) {
                    if ($row->$alias) {
                        $rolesWithRestrictions[$alias] = true;
                        $roles[] = $alias;
                    }
                }

                if ($cache) {
                    self::$knownGrants[$tableName][$row->id] = $roles;
                }
            }
        }

        self::$knownGrants[$tableName][spl_object_hash($filter)] = array_merge(
            $rolesWithoutRestrictions,
            array_keys($rolesWithRestrictions)
        );
    }

    /**
     * Check if any of the given roles grants the permission
     *
     * @param string $permission
     * @param array $roles
     *
     * @return bool
     */
    protected function checkGrants($permission, $roles)
    {
        if (empty($roles)) {
            return false;
        }

        foreach ($this->getAuth()->getUser()->getRoles() as $role) {
            if (! $role->grants($permission)) {
                continue;
            }

            if (in_array($role->getName(), $roles, true)) {
                return true;
            }
        }

        return false;
    }
}
