<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use Icinga\Module\Icingadb\Common\HostStates;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\ServiceStates;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Util\PerfDataSet;
use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Widget\CheckAttempt;
use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Module\Icingadb\Widget\IconImage;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Text;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeSince;
use ipl\Web\Widget\TimeUntil;

abstract class StateRowItem extends BaseStateRowItem
{
    /** @var StateItemTable */
    protected $list;

    protected function getHandledIcon(): string
    {
        switch (true) {
            case $this->item->state->in_downtime:
                return Icons::IN_DOWNTIME;
            case $this->item->state->is_acknowledged:
                return Icons::IS_ACKNOWLEDGED;
            case $this->item->state->is_flapping:
                return Icons::IS_FLAPPING;
            default:
                return Icons::HOST_DOWN;
        }
    }

    protected function assembleVisual(BaseHtmlElement $visual)
    {
        $stateBall = new StateBall($this->item->state->getStateText(), StateBall::SIZE_LARGE);

        if ($this->item->state->is_handled) {
            $stateBall->addHtml(new Icon($this->getHandledIcon()));
            $stateBall->getAttributes()->add('class', 'handled');
        }

        $visual->addHtml($stateBall);
        if ($this->item->state->state_type === 'soft') {
            $visual->addHtml(new CheckAttempt(
                (int) $this->item->state->check_attempt,
                (int) $this->item->max_check_attempts
            ));
        }
    }

    protected function assembleCell(BaseHtmlElement $cell, string $path, $value)
    {
        switch (true) {
            case $path === 'state.output':
            case $path === 'state.long_output':
                if (empty($value)) {
                    $pluginOutput = new EmptyState(t('Output unavailable.'));
                } else {
                    $pluginOutput = new PluginOutputContainer(PluginOutput::fromObject($this->item));
                }

                $cell->addHtml($pluginOutput)
                    ->getAttributes()
                    ->add('class', 'has-plugin-output');
                break;
            case $path === 'state.soft_state':
            case $path === 'state.hard_state':
            case $path === 'state.previous_soft_state':
            case $path === 'state.previous_hard_state':
                $stateType = substr($path, 6);
                if ($this->item instanceof Host) {
                    $stateName = HostStates::translated($this->item->state->$stateType);
                } else {
                    $stateName = ServiceStates::translated($this->item->state->$stateType);
                }

                $cell->addHtml(Text::create($stateName));
                break;
            case $path === 'state.last_update':
            case $path === 'state.last_state_change':
                $column = substr($path, 6);
                $cell->addHtml(new TimeSince($this->item->state->$column->getTimestamp()));
                break;
            case $path === 'state.next_check':
            case $path === 'state.next_update':
                $column = substr($path, 6);
                $cell->addHtml(new TimeUntil($this->item->state->$column->getTimestamp()));
                break;
            case $path === 'state.performance_data':
            case $path === 'state.normalized_performance_data':
                $perfdataContainer = new HtmlElement('div', Attributes::create(['class' => 'performance-data']));

                $pieChartData = PerfDataSet::fromString($this->item->state->normalized_performance_data)->asArray();
                foreach ($pieChartData as $perfdata) {
                    if ($perfdata->isVisualizable()) {
                        $perfdataContainer->addHtml(new HtmlString($perfdata->asInlinePie()->render()));
                    }
                }

                $cell->addHtml($perfdataContainer)
                    ->getAttributes()
                    ->add('class', 'has-performance-data');
                break;
            case $path === 'is_volatile':
            case $path === 'host.is_volatile':
            case substr($path, -8) == '_enabled':
            case (bool) preg_match('/state\.(is_|in_)/', $path):
                if ($value) {
                    $cell->addHtml(new Icon('check'));
                }

                break;
            case $path === 'icon_image.icon_image':
                $cell->addHtml(new IconImage($value, $this->item->icon_image_alt))
                    ->getAttributes()
                    ->add('class', 'has-icon-images');
                break;
            default:
                if (preg_match('/(^id|_id|.id|_checksum|_bin)$/', $path)) {
                    $value = bin2hex($value);
                }

                $cell->addHtml(Text::create($value));
        }
    }
}
