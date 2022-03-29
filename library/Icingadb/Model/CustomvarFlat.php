<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Model\Behavior\Binary;
use Icinga\Module\Icingadb\Model\Behavior\FlattenedObjectVars;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;
use Traversable;

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
        $behaviors->add(new FlattenedObjectVars());

        $behaviors->add(new Binary([
            'id',
            'environment_id',
            'customvar_id',
            'flatname_checksum'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('customvar', Customvar::class);

        $relations->belongsToMany('checkcommand', Checkcommand::class)
            ->through(CheckcommandCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('eventcommand', Eventcommand::class)
            ->through(EventcommandCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('host', Host::class)
            ->through(HostCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('hostgroup', Hostgroup::class)
            ->through(HostgroupCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('notification', Notification::class)
            ->through(NotificationCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('notificationcommand', Notificationcommand::class)
            ->through(NotificationcommandCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('service', Service::class)
            ->through(ServiceCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('servicegroup', Servicegroup::class)
            ->through(ServicegroupCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('timeperiod', Timeperiod::class)
            ->through(TimeperiodCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('user', User::class)
            ->through(UserCustomvar::class)
            ->setCandidateKey('customvar_id');
        $relations->belongsToMany('usergroup', Usergroup::class)
            ->through(UsergroupCustomvar::class)
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
        $registerValue = function (&$data, $path, $value) use (&$registerValue) {
            $step = array_shift($path);
            $pos = null;
            if (preg_match('/\[(\d+)]$/', $step, $m)) {
                $step = substr($step, 0, -strlen($m[0]));
                array_unshift($path, $m[1]);
            }

            if (! empty($path)) {
                if (! isset($data[$step])) {
                    $data[$step] = [];
                }

                $registerValue($data[$step], $path, $value);
            } else {
                $data[$step] = $value;
            }
        };

        $vars = [];
        foreach ($flattenedVars as $var) {
            $path = explode('.', $var->flatname);
            $registerValue($vars, $path, $var->flatvalue);
        }

        return $vars;
    }
}
