<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\ProvidedHook\Reporting\Common\SlaTimeline;
use ipl\Orm\Relations;
use ipl\Orm\UnionModel;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter;

class HostSlaHistory extends UnionModel
{
    protected $slaEndTimes = [];

    public function getTableName()
    {
        return 'host';
    }

    public function getKeyName()
    {
        return 'host_id';
    }

    public function getColumns()
    {
        return [
            'host_id',
            'display_name',
            'event_time',
            'event_type',
            'hard_state',
            'previous_hard_state'
        ];
    }

    public static function on(Connection $db, array $slaEndTimes = [])
    {
        $query = parent::on($db);
        $query->getModel()->setSlaEndTimes($slaEndTimes);

        $downtimeFilter = Filter::unlike('sla_history_downtime.service_id', '*');

        $unions = $query->getUnions();
        $unions[0]->filter($downtimeFilter);
        $unions[1]->filter($downtimeFilter);
        $unions[2]->filter(Filter::unlike('sla_history_state.service_id', '*'));

        return $query;
    }

    public function getUnions()
    {
        $unions = [
            [
                Host::class,
                [
                    'sla_history_downtime'
                ],
                [
                    'display_name'        => 'host.display_name',
                    'event_time'          => 'sla_history_downtime.downtime_start',
                    'event_type'          => new Expression(SlaTimeline::DOWNTIME_START),
                    'hard_state'          => new Expression('NULL'),
                    'host_id'             => 'id',
                    'previous_hard_state' => new Expression('NULL'),
                ]
            ],
            [
                Host::class,
                [
                    'sla_history_downtime'
                ],
                [
                    'display_name'        => 'host.display_name',
                    'event_time'          => 'sla_history_downtime.downtime_end',
                    'event_type'          => new Expression(SlaTimeline::DOWNTIME_END),
                    'hard_state'          => new Expression('NULL'),
                    'host_id'             => 'id',
                    'previous_hard_state' => new Expression('NULL'),
                ]
            ],
            [
                Host::class,
                [
                    'sla_history_state'
                ],
                [
                    'display_name'        => 'display_name',
                    'event_time'          => 'sla_history_state.event_time',
                    'event_type'          => new Expression(SlaTimeline::STATE_CHANGE),
                    'hard_state'          => 'sla_history_state.hard_state',
                    'host_id'             => 'id',
                    'previous_hard_state' => 'sla_history_state.previous_hard_state',
                ]
            ]
        ];

        // Create a union part for all sla interval end times which is supposed to identify the end of the interval.
        foreach ($this->slaEndTimes as $timerange) {
            $unions[] = [
                Host::class,
                [],
                [
                    'display_name'        => 'host.display_name',
                    'event_time'          => new Expression($timerange->end->format('Uv')),
                    'event_type'          => new Expression(SlaTimeline::END_RESULT),
                    'hard_state'          => new Expression('NULL'),
                    'host_id'             => 'id',
                    'previous_hard_state' => new Expression('NULL'),
                ]
            ];
        }

        return $unions;
    }

    public function getDefaultSort()
    {
        return ['event_time', 'display_name', 'event_type'];
    }

    public function createRelations(Relations $relations)
    {
        (new Host())->createRelations($relations);
    }

    public function setSlaEndTimes(array $times): self
    {
        $this->slaEndTimes = $times;

        return $this;
    }
}
