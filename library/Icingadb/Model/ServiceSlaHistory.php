<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\ProvidedHook\Reporting\Common\SlaTimeline;
use ipl\Orm\Relations;
use ipl\Orm\UnionModel;
use ipl\Sql\Connection;
use ipl\Sql\Expression;

class ServiceSlaHistory extends UnionModel
{
    protected $slaEndTimes = [];

    public function getTableName()
    {
        return 'service';
    }

    public function getKeyName()
    {
        return ['host_id', 'service_id'];
    }

    public function getColumns()
    {
        return [
            'host_id',
            'service_id',
            'display_name',
            'host_display_name',
            'event_time',
            'event_type',
            'hard_state',
            'previous_hard_state'
        ];
    }

    public static function on(Connection $db, array $timeranges = [])
    {
        $query = parent::on($db);
        $query->getModel()->setSlaEndTimes($timeranges);

        return $query;
    }

    public function getUnions()
    {
        $unions = [
            [
                Service::class,
                [
                    'host',
                    'sla_history_downtime'
                ],
                [
                    'display_name'        => 'display_name',
                    'host_display_name'   => 'host.display_name',
                    'event_time'          => 'sla_history_downtime.downtime_start',
                    'event_type'          => new Expression(SlaTimeline::DOWNTIME_START),
                    'hard_state'          => new Expression('NULL'),
                    'host_id'             => 'host.id',
                    'service_id'          => 'id',
                    'previous_hard_state' => new Expression('NULL'),
                ]
            ],
            [
                Service::class,
                [
                    'host',
                    'sla_history_downtime'
                ],
                [
                    'display_name'        => 'display_name',
                    'host_display_name'   => 'host.display_name',
                    'event_time'          => 'sla_history_downtime.downtime_end',
                    'event_type'          => new Expression(SlaTimeline::DOWNTIME_END),
                    'hard_state'          => new Expression('NULL'),
                    'host_id'             => 'host.id',
                    'service_id'          => 'id',
                    'previous_hard_state' => new Expression('NULL'),
                ]
            ],
            [
                Service::class,
                [
                    'host',
                    'sla_history_state'
                ],
                [
                    'display_name'        => 'display_name',
                    'host_display_name'   => 'host.display_name',
                    'event_time'          => 'sla_history_state.event_time',
                    'event_type'          => new Expression(SlaTimeline::STATE_CHANGE),
                    'hard_state'          => 'sla_history_state.hard_state',
                    'host_id'             => 'host.id',
                    'service_id'          => 'id',
                    'previous_hard_state' => 'sla_history_state.previous_hard_state',
                ]
            ]
        ];

        // Create a union part for all sla interval end times which is supposed to identify the end of the interval.
        foreach ($this->slaEndTimes as $timerange) {
            $unions[] = [
                Service::class,
                [
                    'host'
                ],
                [
                    'display_name'        => 'display_name',
                    'host_display_name'   => 'host.display_name',
                    'event_time'          => new Expression($timerange->end->format('Uv')),
                    'event_type'          => new Expression(SlaTimeline::END_RESULT),
                    'hard_state'          => new Expression('NULL'),
                    'host_id'             => 'host.id',
                    'service_id'          => 'id',
                    'previous_hard_state' => new Expression('NULL'),
                ]
            ];
        }

        return $unions;
    }

    public function getDefaultSort()
    {
        return ['event_time', 'host_display_name', 'display_name', 'event_type'];
    }

    public function createRelations(Relations $relations)
    {
        (new Service())->createRelations($relations);
    }

    /**
     * Set all the sla interval end time to be part of the union query
     *
     * @param array $times
     *
     * @return $this
     */
    public function setSlaEndTimes(array $times): self
    {
        $this->slaEndTimes = $times;

        return $this;
    }
}
