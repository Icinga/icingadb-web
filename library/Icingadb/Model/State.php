<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Model;

use DateTime;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Model\Behavior\BoolCast;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Web\Widget\Icon;

/**
 * Base class for the {@link HostState} and {@link ServiceState} models providing common columns.
 *
 * @property string $id
 * @property string $environment_id The environment id
 * @property string $state_type The state type (hard or soft)
 * @property int $soft_state The current soft state code (0 = OK, 1 = WARNING, 2 = CRITICAL, 3 = UNKNOWN)
 * @property int $hard_state The current hard state code (0 = OK, 1 = WARNING, 2 = CRITICAL, 3 = UNKNOWN)
 * @property int $previous_soft_state The previous soft state code (0 = OK, 1 = WARNING, 2 = CRITICAL, 3 = UNKNOWN)
 * @property int $previous_hard_state The previous hard state code (0 = OK, 1 = WARNING, 2 = CRITICAL, 3 = UNKNOWN)
 * @property int $check_attempt The check attempt count
 * @property int $severity The calculated severity
 * @property ?string $output The check output
 * @property ?string $long_output The long check output
 * @property ?string $performance_data The performance data
 * @property ?string $normalized_performance_data The normalized performance data (converted ms to s, GiB to byte etc.)
 * @property ?string $check_commandline The executed check command
 * @property bool $is_problem Whether in non-OK state
 * @property bool $is_handled Whether the state is handled
 * @property bool $is_reachable Whether the node is reachable
 * @property bool $is_flapping Whether the state is flapping
 * @property bool $is_overdue Whether the check is overdue
 * @property bool|string $is_acknowledged Whether the state is acknowledged (bool), can also be `sticky` (string)
 * @property ?string $acknowledgement_comment_id The id of acknowledgement comment
 * @property ?string $last_comment_id The id of last comment
 * @property bool $in_downtime Whether the node is in downtime
 * @property ?int $execution_time The check execution time
 * @property ?int $latency The check latency
 * @property ?int $check_timeout The check timeout
 * @property ?string $check_source The name of the node that executes the check
 * @property ?string $scheduling_source The name of the node that schedules the check
 * @property ?DateTime $last_update The time when the node was last updated
 * @property DateTime $last_state_change The time when the node last got a status change
 * @property DateTime $next_check The time when the node will execute the next check
 * @property DateTime $next_update The time when the next check of the node is expected to end
 * @property bool $affects_children Whether any of the children is affected if there is a problem
 */
abstract class State extends Model
{
    /**
     * Get the state as the textual representation
     *
     * @return string
     */
    abstract public function getStateText(): string;

    /**
     * Get the state as the translated textual representation
     *
     * @return string
     */
    abstract public function getStateTextTranslated(): string;

    public function getColumns()
    {
        return [
            'environment_id',
            'state_type',
            'soft_state',
            'hard_state',
            'previous_soft_state',
            'previous_hard_state',
            'check_attempt',
            'severity',
            'output',
            'long_output',
            'performance_data',
            'normalized_performance_data',
            'check_commandline',
            'is_problem',
            'is_handled',
            'is_reachable',
            'is_flapping',
            'is_overdue',
            'is_acknowledged',
            'acknowledgement_comment_id',
            'last_comment_id',
            'in_downtime',
            'execution_time',
            'latency',
            'check_timeout',
            'check_source',
            'scheduling_source',
            'last_update',
            'last_state_change',
            'next_check',
            'next_update',
            'affects_children'
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new BoolCast([
            'is_problem',
            'is_handled',
            'is_reachable',
            'is_flapping',
            'is_overdue',
            'is_acknowledged',
            'in_downtime',
            'affects_children'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'last_update',
            'last_state_change',
            'next_check',
            'next_update'
        ]));

        $behaviors->add(new Binary([
            $this->getKeyName(),
            'environment_id',
            'acknowledgement_comment_id',
            'last_comment_id'
        ]));
    }

    /**
     * Get the state icon
     *
     * @return Icon|null
     */
    public function getIcon(): ?Icon
    {
        $icon = null;
        switch (true) {
            case $this->is_acknowledged:
                $icon = new Icon(Icons::IS_ACKNOWLEDGED);

                break;
            case $this->in_downtime:
                $icon = new Icon(
                    Icons::IN_DOWNTIME,
                    ['title' => sprintf(
                        '%s (%s)',
                        strtoupper($this->getStateTextTranslated()),
                        $this->is_handled ? t('handled by Downtime') : t('in Downtime')
                    )]
                );

                break;
            case $this->is_flapping:
                $icon = new Icon(Icons::IS_FLAPPING);

                break;
            case ! $this->is_reachable:
                $icon = new Icon(Icons::HOST_DOWN, [
                    'title' => sprintf(
                        '%s (%s)',
                        strtoupper($this->getStateTextTranslated()),
                        t('is unreachable')
                    )
                ]);

                break;
            case $this->is_handled:
                $icon = new Icon(Icons::HOST_DOWN);

                break;
        }

        return $icon;
    }
}
