<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Contract\RewriteFilterBehavior;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Orm\Relations;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Filter\Condition;
use Traversable;

/**
 * @property string $id
 * @property string $environment_id
 * @property string $customvar_id
 * @property string $flatname_checksum
 * @property string $flatname
 * @property ?string $flatvalue
 */
class CustomvarFlat extends Model
{
    public function getTableName()
    {
        return 'customvar_flat';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'environment_id',
            'customvar_id',
            'flatname_checksum',
            'flatname',
            'flatvalue'
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'customvar_id',
            'flatname_checksum'
        ]));
        $behaviors->add(new class implements RewriteFilterBehavior {
            public function rewriteCondition(Condition $condition, $relation = null)
            {
                if ($condition->metaData()->has('requiresTransformation')) {
                    /** @var string $columnName */
                    $columnName = $condition->metaData()->get('columnName');
                    $nameFilter = Filter::like($relation . 'flatname', $columnName);
                    $class = get_class($condition);
                    $valueFilter = new $class($relation . 'flatvalue', $condition->getValue());

                    return Filter::all($nameFilter, $valueFilter);
                }
            }
        });
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('customvar', Customvar::class);

        $relations->belongsToMany('checkcommand', Checkcommand::class)
            ->through(CheckcommandCustomvar::class)
            ->setThroughAlias('t_checkcommand_customvar')
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('eventcommand', Eventcommand::class)
            ->through(EventcommandCustomvar::class)
            ->setThroughAlias('t_eventcommand_customvar')
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('host', Host::class)
            ->through(HostCustomvar::class)
            ->setThroughAlias('t_host_customvar')
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('hostgroup', Hostgroup::class)
            ->through(HostgroupCustomvar::class)
            ->setThroughAlias('t_hostgroup_customvar')
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('notification', Notification::class)
            ->through(NotificationCustomvar::class)
            ->setThroughAlias('t_notification_customvar')
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('notificationcommand', Notificationcommand::class)
            ->through(NotificationcommandCustomvar::class)
            ->setThroughAlias('t_notificationcommand_customvar')
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('service', Service::class)
            ->through(ServiceCustomvar::class)
            ->setThroughAlias('t_service_customvar')
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('servicegroup', Servicegroup::class)
            ->through(ServicegroupCustomvar::class)
            ->setThroughAlias('t_servicegroup_customvar')
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('timeperiod', Timeperiod::class)
            ->through(TimeperiodCustomvar::class)
            ->setThroughAlias('t_timeperiod_customvar')
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('user', User::class)
            ->through(UserCustomvar::class)
            ->setThroughAlias('t_user_customvar')
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('usergroup', Usergroup::class)
            ->through(UsergroupCustomvar::class)
            ->setThroughAlias('t_usergroup_customvar')
            ->setCandidateKey('customvar_id');
    }

    /**
     * Restore flattened custom variables to their previous structure
     *
     * @param Traversable $flattenedVars
     *
     * @return array
     */
    public function unFlattenVars(Traversable $flattenedVars): array
    {
        $registerValue = function (&$data, $source, $path, $value) use (&$registerValue) {
            $step = array_shift($path);

            $isIndex = (bool) preg_match('/^\[(\d+)]$/', $step, $m);
            if ($isIndex) {
                $step = $m[1];
            }

            if ($source !== null) {
                while (! isset($source[$step])) {
                    if ($isIndex) {
                        $step = sprintf('[%d]', $step);
                        $isIndex = false;
                    } else {
                        if (empty($path)) {
                            break;
                        }

                        $step = implode('.', [$step, array_shift($path)]);
                    }
                }
            }

            if (! empty($path)) {
                if (! isset($data[$step])) {
                    $data[$step] = [];
                }

                $registerValue($data[$step], $source[$step] ?? null, $path, $value);
            } else {
                // Since empty custom vars of type dictionaries and arrays have null values in customvar_flat table,
                // we won't be able to render them as such. Therefore, we have to use the value of the `customvar`
                // table if it's not null, otherwise the current value, which is a "null" string.
                $data[$step] = $value === null && ($source[$step] ?? null) === [] ? $source[$step] : $value;
            }
        };

        if ($flattenedVars instanceof Query) {
            $flattenedVars->withColumns(['customvar.name', 'customvar.value']);
        }

        $vars = [];
        foreach ($flattenedVars as $var) {
            if (isset($var->customvar->name)) {
                $var->customvar->value = json_decode($var->customvar->value, true);

                $realName = $var->customvar->name;
                $source = [$realName => $var->customvar->value];

                $sourcePath = ltrim(substr($var->flatname, strlen($realName)), '.');
                $path = array_merge(
                    [$realName],
                    $sourcePath
                        ? preg_split('/(?<=\w|])\.|(?<!^|\.)(?=\[)/', $sourcePath)
                        : []
                );
            } else {
                $path = explode('.', $var->flatname);
                $source = null;
            }

            $registerValue($vars, $source, $path, $var->flatvalue);

            if (isset($var->customvar->name)) {
                $var->customvar->name = null;
                $var->customvar->value = null;
            }
        }

        return $vars;
    }
}
