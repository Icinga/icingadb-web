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

        $uniqueId = spl_object_hash($for);
        if (! isset(self::$knownGrants[$uniqueId])) {
            self::$knownGrants[$uniqueId] = $self->loadGrants(
                get_class($for),
                Filter::equal($for->getKeyName(), $for->{$for->getKeyName()})
            );
        }

        return $self->checkGrants($permission, self::$knownGrants[$uniqueId]);
    }

    /**
     * Check whether the permission is granted on objects matching the type and filter
     *
     * The check will not be performed on every object matching the filter. The result
     * only allows to determine whether the permission is granted on **any** or *none*
     * of the objects in question.
     *
     * @param string $permission
     * @param string $type
     * @param Filter\Rule $filter
     *
     * @return bool
     */
    public static function grantsOnType($permission, $type, Filter\Rule $filter)
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
        if (! isset(self::$knownGrants[$uniqueId])) {
            self::$knownGrants[$uniqueId] = $self->loadGrants($for, $filter);
        }

        return $self->checkGrants($permission, self::$knownGrants[$uniqueId]);
    }

    /**
     * Load all the user's roles that grant access to at least one object matching the filter
     *
     * @param string $model The class path to the object model
     * @param Filter\Rule $filter
     *
     * @return array
     */
    protected function loadGrants($model, Filter\Rule $filter)
    {
        $roles = [];
        $columns = [];
        foreach ($this->getAuth()->getUser()->getRoles() as $role) {
            /** @var Model $model */
            $subQuery = $model::on($this->getDb())->columns([new Expression('1')]);

            $roleFilter = Filter::all();
            if (($restriction = $role->getRestrictions('icingadb/filter/objects'))) {
                $roleFilter->add($this->parseRestriction($restriction, 'icingadb/filter/objects'));
            }

            $tableName = $subQuery->getModel()->getTableName();
            if ($tableName === 'host' || $tableName === 'service') {
                if (($restriction = $role->getRestrictions('icingadb/filter/hosts'))) {
                    $roleFilter->add($this->parseRestriction($restriction, 'icingadb/filter/hosts'));
                }
            }

            if ($tableName === 'service' && ($restriction = $role->getRestrictions('icingadb/filter/services'))) {
                $roleFilter->add($this->parseRestriction($restriction, 'icingadb/filter/services'));
            }

            if ($roleFilter->isEmpty()) {
                $roles[] = $role->getName();
                continue;
            }

            FilterProcessor::apply($roleFilter->add($filter), $subQuery);
            $columns[$role->getName()] = $subQuery->limit(1)->assembleSelect()->resetOrderBy();
        }

        if (! empty($columns)) {
            $row = $this->getDb()->fetchOne((new Select())->columns($columns));
            foreach ($columns as $column => $_) {
                if ($row->$column) {
                    $roles[] = $column;
                }
            }
        }

        return $roles;
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
