<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Date\DateFormatter;
use Icinga\Module\Icingadb\Widget\CheckAttempt;
use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Util\Format;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\FormattedString;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Text;
use ipl\Web\Common\Card;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\TimeSince;
use ipl\Web\Widget\TimeUntil;
use ipl\Web\Widget\VerticalKeyValue;

class CheckStatistics extends Card
{
    const TOP_LEFT_BUBBLE_FLAG = <<<'SVG'
<svg viewBox='0 0 12 12' xmlns='http://www.w3.org/2000/svg'>
    <path class='bg' d='M0 0L13 13L3.15334e-06 13L0 0Z'/>
    <path class='border' fill-rule='evenodd' clip-rule='evenodd'
          d='M0 0L3.3959e-06 14L14 14L0 0ZM1 2.41421L1 13L11.5858 13L1 2.41421Z'/>
</svg>
SVG;

    const TOP_RIGHT_BUBBLE_FLAG = <<<'SVG'
<svg viewBox='0 0 12 12' xmlns='http://www.w3.org/2000/svg'>
    <path class='bg' d="M12 0L-1 13L12 13L12 0Z"/>
    <path class='border' fill-rule="evenodd" clip-rule="evenodd"
          d="M12 0L12 14L-2 14L12 0ZM11 2.41421L11 13L0.414213 13L11 2.41421Z"/>
</svg>
SVG;


    protected $object;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => ['progress-bar', 'check-statistics']];

    public function __construct($object)
    {
        $this->object = $object;
    }

    protected function assembleBody(BaseHtmlElement $body)
    {
        $hPadding = 10;
        $durationScale = 80;
        $checkInterval = $this->getCheckInterval();

        $timeline = new HtmlElement('div', Attributes::create(['class' => ['check-timeline', 'timeline']]));
        $above = new HtmlElement('ul', Attributes::create(['class' => 'above']));
        $below = new HtmlElement('ul', Attributes::create(['class' => 'below']));
        $progressBar = new HtmlElement('div', Attributes::create(['class' => 'bar']));
        $overdueBar = null;

        $now = time();
        $executionTime = ($this->object->state->execution_time / 1000) + ($this->object->state->latency / 1000);

        $nextCheckTime = $this->object->state->next_check !== null
            ? $this->object->state->next_check->getTimestamp()
            : null;
        if ($this->object->state->is_overdue) {
            $nextCheckTime = $this->object->state->next_update->getTimestamp();

            $durationScale = 60;

            $overdueBar = new HtmlElement(
                'div',
                Attributes::create(['class' => 'timeline-overlay']),
                new HtmlElement('div', Attributes::create(['class' => 'now']))
            );

            $above->addHtml(new HtmlElement(
                'li',
                Attributes::create(['class' => 'now']),
                new HtmlElement(
                    'div',
                    Attributes::create(['class' => 'bubble']),
                    new HtmlElement('strong', null, Text::create(t('Now')))
                )
            ));

            $this->getAttributes()->add('class', 'check-overdue');
        } else {
            $progressBar->addHtml(new HtmlElement('div', Attributes::create(['class' => 'now'])));
        }

        if ($nextCheckTime !== null && ! $this->object->state->is_overdue && $nextCheckTime < $now) {
            // If the next check is already in the past but not overdue, it means the check is probably running.
            // Icinga only updates the state once the check reports a result, that's why we have to simulate the
            // execution start and end time, as well as the next check time.
            $lastUpdateTime = $nextCheckTime;
            $nextCheckTime = $this->object->state->next_update->getTimestamp() - $executionTime;
            $executionEndTime = $lastUpdateTime + $executionTime;
        } else {
            $lastUpdateTime = $this->object->state->last_update !== null
                ? $this->object->state->last_update->getTimestamp() - $executionTime
                : null;
            $executionEndTime = $this->object->state->last_update !== null
                ? $this->object->state->last_update->getTimestamp()
                : null;
        }

        if ($this->object->state->is_overdue) {
            $leftNow = 100;
        } elseif ($nextCheckTime === null) {
            $leftNow = 0;
        } else {
            $leftNow = 100 * (1 - ($nextCheckTime - time()) / ($nextCheckTime - $lastUpdateTime));
            if ($leftNow > 100) {
                $leftNow = 100;
            } elseif ($leftNow < 0) {
                $leftNow = 0;
            }
        }

        $progressBar->getAttributes()->add('style', sprintf('width: %s%%', $leftNow));

        $leftExecutionEnd = $nextCheckTime !== null ? $durationScale * (
            1 - ($nextCheckTime - $executionEndTime) / ($nextCheckTime - $lastUpdateTime)
        ) : 0;

        $markerLast = new HtmlElement('div', Attributes::create([
            'class' => ['highlighted', 'marker', 'left'],
            'title' => $lastUpdateTime !== null ? DateFormatter::formatDateTime($lastUpdateTime) : null
        ]));
        $markerNext = new HtmlElement('div', Attributes::create([
            'class' => ['highlighted', 'marker', 'right'],
            'title' => $nextCheckTime !== null ? DateFormatter::formatDateTime($nextCheckTime) : null
        ]));
        $markerExecutionEnd = new HtmlElement('div', Attributes::create([
            'class' => ['highlighted', 'marker'],
            'style' => sprintf('left: %F%%', $hPadding + $leftExecutionEnd),
        ]));

        $progress = new HtmlElement('div', Attributes::create([
            'class' => ['progress', time() < $executionEndTime ? 'running' : null]
        ]), $progressBar);
        if ($nextCheckTime !== null) {
            $progress->addAttributes([
                'data-animate-progress' => true,
                'data-start-time' => $lastUpdateTime,
                'data-end-time' => $nextCheckTime,
                'data-switch-after' => $executionTime,
                'data-switch-class' => 'running'
            ]);
        }

        $timeline->addHtml(
            $progress,
            $markerLast,
            $markerExecutionEnd,
            $markerNext
        )->add($overdueBar);

        $executionStart = new HtmlElement(
            'li',
            Attributes::create(['class' => 'left']),
            new HtmlElement(
                'div',
                Attributes::create(['class' => ['bubble', 'upwards', 'top-right-aligned']]),
                new VerticalKeyValue(
                    t('Execution Start'),
                    $lastUpdateTime ? new TimeAgo($lastUpdateTime) : t('PENDING')
                ),
                HtmlString::create(self::TOP_RIGHT_BUBBLE_FLAG)
            )
        );
        $executionEnd = new HtmlElement(
            'li',
            Attributes::create([
                'class' => 'positioned',
                'style' => sprintf('left: %F%%', $hPadding + $leftExecutionEnd)
            ]),
            new HtmlElement(
                'div',
                Attributes::create(['class' => ['bubble', 'upwards', 'top-left-aligned']]),
                new VerticalKeyValue(
                    t('Execution End'),
                    $executionEndTime !== null
                        ? ($executionEndTime > $now
                        ? new TimeUntil($executionEndTime)
                        : new TimeAgo($executionEndTime))
                        : t('PENDING')
                ),
                HtmlString::create(self::TOP_LEFT_BUBBLE_FLAG)
            )
        );

        $intervalLine = new HtmlElement(
            'li',
            Attributes::create([
                'class' => 'interval-line',
                'style' => sprintf(
                    'left: %F%%; width: %F%%;',
                    $hPadding + $leftExecutionEnd,
                    $durationScale - $leftExecutionEnd
                )
            ]),
            new VerticalKeyValue(
                t('Interval'),
                $checkInterval
                    ? Format::seconds($checkInterval)
                    : (new EmptyState(t('n. a.')))->setTag('span')
            )
        );
        $executionLine = new HtmlElement(
            'li',
            Attributes::create([
                'class' => ['interval-line', 'execution-line'],
                'style' => sprintf('left: %F%%; width: %F%%;', $hPadding, $leftExecutionEnd)
            ]),
            new VerticalKeyValue(
                sprintf('%s / %s', t('Execution Time'), t('Latency')),
                FormattedString::create(
                    '%s / %s',
                    $this->object->state->execution_time !== null
                        ? Format::seconds($this->object->state->execution_time / 1000)
                        : (new EmptyState(t('n. a.')))->setTag('span'),
                    $this->object->state->latency !== null
                        ? Format::seconds($this->object->state->latency / 1000)
                        : (new EmptyState(t('n. a.')))->setTag('span')
                )
            )
        );
        if ($executionEndTime !== null) {
            $executionLine->addHtml(new HtmlElement('div', Attributes::create(['class' => 'start'])));
            $executionLine->addHtml(new HtmlElement('div', Attributes::create(['class' => 'end'])));
        }

        if ($this->isChecksDisabled()) {
            $nextCheckBubbleContent = new VerticalKeyValue(
                t('Next Check'),
                t('n.a')
            );

            $this->addAttributes(['class' => 'checks-disabled']);
        } else {
            $nextCheckBubbleContent = $this->object->state->is_overdue
                ? new VerticalKeyValue(t('Overdue'), new TimeSince($nextCheckTime))
                : new VerticalKeyValue(
                    t('Next Check'),
                    $nextCheckTime !== null
                        ? ($nextCheckTime > $now
                            ? new TimeUntil($nextCheckTime)
                            : new TimeAgo($nextCheckTime))
                        : t('PENDING')
                );
        }

        $nextCheck = new HtmlElement(
            'li',
            Attributes::create(['class' => 'right']),
            new HtmlElement(
                'div',
                Attributes::create(['class' => ['bubble', 'upwards']]),
                $nextCheckBubbleContent
            )
        );

        $above->addHtml($executionLine);

        $below->addHtml(
            $executionStart,
            $executionEnd,
            $intervalLine,
            $nextCheck
        );

        $body->addHtml($above, $timeline, $below);
    }

    /**
     * Checks if both active and passive checks are disabled
     *
     * @return bool
     */
    protected function isChecksDisabled(): bool
    {
        return ! ($this->object->active_checks_enabled || $this->object->passive_checks_enabled);
    }

    protected function assembleHeader(BaseHtmlElement $header)
    {
        $checkSource = (new EmptyState(t('n. a.')))->setTag('span');
        if ($this->object->state->check_source) {
            $checkSource = [
                new StateBall($this->object->state->is_reachable ? 'up' : 'down', StateBall::SIZE_MEDIUM),
                ' ',
                $this->object->state->check_source
            ];
        }

        $header->addHtml(
            new VerticalKeyValue(t('Command'), $this->object->checkcommand_name),
            new VerticalKeyValue(
                t('Scheduling Source'),
                $this->object->state->scheduling_source ?? (new EmptyState(t('n. a.')))->setTag('span')
            )
        );

        if ($this->object->timeperiod->id) {
            $header->addHtml(new VerticalKeyValue(
                t('Timeperiod'),
                $this->object->timeperiod->display_name ?? $this->object->timeperiod->name
            ));
        }

        $header->addHtml(
            new VerticalKeyValue(
                t('Attempts'),
                new CheckAttempt((int) $this->object->state->check_attempt, (int) $this->object->max_check_attempts)
            ),
            new VerticalKeyValue(t('Check Source'), $checkSource)
        );
    }

    /**
     * Get the active `check_interval` OR `check_retry_interval`
     *
     * @return int
     */
    protected function getCheckInterval(): int
    {
        if (! ($this->object->state->is_problem && $this->object->state->state_type === 'soft')) {
            return $this->object->check_interval;
        }

        $delay = ($this->object->state->execution_time + $this->object->state->latency) / 1000 + 5;
        $interval = $this->object->state->next_check->getTimestamp()
            - $this->object->state->last_update->getTimestamp();

        // In case passive check is used, the check_retry_interval has no effect.
        // Since there is no flag in the database to check if the passive check was triggered.
        // We have to manually check if the check_retry_interval matches the calculated interval.
        if (
            $this->object->check_retry_interval - $delay <= $interval
            && $this->object->check_retry_interval + $delay >= $interval
        ) {
            return $this->object->check_retry_interval;
        }

        return $this->object->check_interval;
    }

    protected function assemble()
    {
        parent::assemble();

        if ($this->isChecksDisabled()) {
            $this->addHtml(new HtmlElement(
                'div',
                Attributes::create(['class' => 'checks-disabled-overlay']),
                new HtmlElement(
                    'strong',
                    Attributes::create(['class' => 'notes']),
                    Text::create(t('active and passive checks are disabled'))
                )
            ));
        }
    }
}
