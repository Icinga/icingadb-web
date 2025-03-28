<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\View;

use Icinga\Module\Icingadb\Common\HostStates;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\ServiceStates;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Util\PerfDataSet;
use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Widget\CheckAttempt;
use Icinga\Module\Icingadb\Widget\IconImage;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use Icinga\Module\Icingadb\Widget\StateChange;
use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Web\Common\ItemRenderer;
use ipl\Web\Layout\ItemLayout;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeSince;

/**
 * @template Item of Host|Service
 *
 * @implements ItemRenderer<Item>
 */
abstract class BaseHostAndServiceRenderer implements ItemRenderer
{
    use Translation;

    /**
     * Create subject for the given item
     *
     * @param Item $item The item to create subject for
     *
     * @param string $layout The name of the layout
     *
     * @return ValidHtml
     */
    abstract protected function createSubject($item, string $layout): ValidHtml;

    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        if ($layout === 'header') {
            if ($item->state->state_type === 'soft') {
                $stateType = 'soft_state';
                $previousStateType = 'previous_soft_state';

                if ($item->state->previous_soft_state === 0) {
                    $previousStateType = 'hard_state';
                }
            } else {
                $stateType = 'hard_state';
                $previousStateType = 'previous_hard_state';

                if ($item->state->hard_state === $item->state->previous_hard_state) {
                    $previousStateType = 'previous_soft_state';
                }
            }

            if ($item instanceof Host) {
                $state = HostStates::text($item->state->$stateType);
                $previousState = HostStates::text($item->state->$previousStateType);
            } else {
                $state = ServiceStates::text($item->state->$stateType);
                $previousState = ServiceStates::text($item->state->$previousStateType);
            }

            $stateChange = new StateChange($state, $previousState);
            if ($stateType === 'soft_state') {
                $stateChange->setCurrentStateBallSize(StateBall::SIZE_MEDIUM_LARGE);
            }

            if ($previousStateType === 'previous_soft_state') {
                $stateChange->setPreviousStateBallSize(StateBall::SIZE_MEDIUM_LARGE);
                if ($stateType === 'soft_state') {
                    $visual->getAttributes()->add('class', 'small-state-change');
                }
            }

            $stateChange->setIcon($item->state->getIcon());
            $stateChange->setHandled(
                $item->state->is_problem && ($item->state->is_handled || ! $item->state->is_reachable)
            );

            $visual->addHtml($stateChange);

            return;
        }

        $ballSize = $layout === 'minimal' ? StateBall::SIZE_BIG : StateBall::SIZE_LARGE;

        $stateBall = new StateBall($item->state->getStateText(), $ballSize);
        $stateBall->add($item->state->getIcon());
        if ($item->state->is_problem && ($item->state->is_handled || ! $item->state->is_reachable)) {
            $stateBall->getAttributes()->add('class', 'handled');
        }

        $visual->addHtml($stateBall);
        if ($layout !== 'minimal' && $item->state->state_type === 'soft') {
            $visual->addHtml(
                new CheckAttempt((int) $item->state->check_attempt, (int) $item->max_check_attempts)
            );
        }
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        if ($item->state->soft_state === null && $item->state->output === null) {
            $caption->addHtml(Text::create($this->translate('Waiting for Icinga DB to synchronize the state.')));
        } else {
            if (empty($item->state->output)) {
                $pluginOutput = new EmptyState($this->translate('Output unavailable.'));
            } else {
                $pluginOutput = new PluginOutputContainer(PluginOutput::fromObject($item));
            }

            $caption->addHtml($pluginOutput);
        }
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        $title->addHtml(Html::sprintf(
            $this->translate('%s is %s', '<hostname> is <state-text>'),
            $this->createSubject($item, $layout),
            Html::tag('span', ['class' => 'state-text'], $item->state->getStateTextTranslated())
        ));

        if (isset($item->state->affects_children) && $item->state->affects_children) {
            $total = (int) $item->total_children;

            if ($total > 1000) {
                $total = '1000+';
                $tooltip = $this->translate('Up to 1000+ affected objects');
            } else {
                $tooltip = sprintf(
                    $this->translatePlural(
                        '%d affected object',
                        'Up to %d affected objects',
                        $total
                    ),
                    $total
                );
            }

            $icon = new Icon(Icons::UNREACHABLE);

            $title->addHtml(new HtmlElement(
                'span',
                Attributes::create([
                    'class' => 'affected-objects',
                    'title' => $tooltip
                ]),
                $icon,
                Text::create($total)
            ));
        }
    }

    public function assembleExtendedInfo($item, HtmlDocument $info, string $layout): void
    {
        if ($item->state->is_overdue) {
            $since = new TimeSince($item->state->next_update->getTimestamp());
            $since->prepend($this->translate('Overdue') . ' ');
            $since->prependHtml(new Icon(Icons::WARNING));

            $info->addHtml($since);
        } elseif ($item->state->last_state_change !== null && $item->state->last_state_change->getTimestamp() > 0) {
            $info->addHtml(new TimeSince($item->state->last_state_change->getTimestamp()));
        }
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
        $pieChartLimit = 5;
        $statusIcons = new HtmlElement('div', Attributes::create(['class' => 'status-icons']));
        $isService = $item instanceof Service;

        if (
            ($isService && $item->state->last_comment->service_id === $item->id)
            || $item->state->last_comment->host_id === $item->id
        ) {
            $comment = $item->state->last_comment;

            if ($isService) {
                $comment->service = $item;
            } else {
                $comment->host = $item;
            }

            $commentItem = new ItemLayout(
                $comment,
                (new CommentRenderer())
                    ->setTicketLinkDisabled()
                    ->setNoObjectLink()
                    ->setNoSubjectLink()
            );

            $statusIcons->addHtml(
                new HtmlElement(
                    'div',
                    Attributes::create(['class' => 'comment-wrapper']),
                    new HtmlElement(
                        'div',
                        $commentItem->getAttributes()->add('class', 'comment-popup'),
                        $commentItem
                    ),
                    (new Icon('comments', ['class' => 'comment-icon']))
                )
            );
        }

        if ($item->state->is_flapping) {
            $title = $isService
                ? sprintf(
                    $this->translate('Service "%s" on "%s" is in flapping state'),
                    $item->display_name,
                    $item->host->display_name
                )
                : sprintf(
                    $this->translate('Host "%s" is in flapping state'),
                    $item->display_name
                );

            $statusIcons->addHtml(new Icon('random', ['title' => $title]));
        }

        if (! $item->notifications_enabled) {
            $statusIcons->addHtml(
                new Icon('bell-slash', ['title' => $this->translate('Notifications disabled')])
            );
        }

        if (! $item->active_checks_enabled) {
            $statusIcons->addHtml(
                new Icon('eye-slash', ['title' => $this->translate('Active checks disabled')])
            );
        }

        $performanceData = new HtmlElement('div', Attributes::create(['class' => 'performance-data']));
        if ($item->state->performance_data) {
            $pieChartData = PerfDataSet::fromString($item->state->normalized_performance_data)->asArray();

            $pies = [];
            foreach ($pieChartData as $i => $perfdata) {
                if ($perfdata->isVisualizable()) {
                    $pies[] = $perfdata->asInlinePie()->render();
                }

                // Check if number of visualizable pie charts is larger than $PIE_CHART_LIMIT
                if (count($pies) > $pieChartLimit) {
                    break;
                }
            }

            $maxVisiblePies = $pieChartLimit - 2;
            $numOfPies = count($pies);
            foreach ($pies as $i => $pie) {
                if (
                    // Show max. 5 elements: if there are more than 5, show 4 + `…`
                    $i > $maxVisiblePies && $numOfPies > $pieChartLimit
                ) {
                    $performanceData->addHtml(new HtmlElement('span', null, Text::create('…')));
                    break;
                }

                $performanceData->addHtml(HtmlString::create($pie));
            }
        }

        if (! $statusIcons->isEmpty()) {
            $footer->addHtml($statusIcons);
        }

        if (! $performanceData->isEmpty()) {
            $footer->addHtml($performanceData);
        }
    }

    public function assemble($item, string $name, HtmlDocument $element, string $layout): bool
    {
        if ($name === 'icon-image') {
            if (isset($item->icon_image->icon_image)) {
                $element->addHtml(new IconImage($item->icon_image->icon_image, $item->icon_image_alt));
            }

            return true;
        }

        return false;
    }
}
