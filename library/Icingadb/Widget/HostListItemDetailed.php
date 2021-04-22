<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\ListItemDetailedLayout;
use Icinga\Module\Icingadb\Compat\CompatPluginOutput;
use Icinga\Module\Icingadb\Util\PerfDataSet;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

class HostListItemDetailed extends BaseHostListItem
{
    use ListItemDetailedLayout;

    /** @var int Max pie charts to be shown */
    const PIE_CHART_LIMIT = 5;

    protected function getStateBallSize()
    {
        return StateBall::SIZE_LARGE;
    }

    protected function assembleFooter(BaseHtmlElement $footer)
    {
        $statusIcons = new HtmlElement('div', ['class' => 'status-icons']);

        // ToDo(fs): Get `has_comments` from database
        if ($this->item->comment->limit(1)->execute()->hasResult()) {
            $statusIcons->add(new Icon('comments', ['title' => t('This item has been commented')]));
        }

        if (! $this->item->notifications_enabled) {
            $statusIcons->add(new Icon('bell-slash', ['title' => t('Notifications disabled')]));
        }

        if (! $this->item->active_checks_enabled) {
            $statusIcons->add(new Icon('eye-slash', ['title' => t('Active checks disabled')]));
        }

        $performanceData = new HtmlElement('div', ['class' => 'performance-data']);
        if ($this->item->state->performance_data) {
            $pieChartData = PerfDataSet::fromString($this->item->state->performance_data)->asArray();

            $pies = [];
            foreach ($pieChartData as $i => $perfdata) {
                if ($perfdata->isVisualizable()) {
                    $pies[] = $perfdata->asInlinePie()->render();
                }

                // Check if number of visualizable pie charts is larger than PIE_CHART_LIMIT
                if (count($pies) > HostListItemDetailed::PIE_CHART_LIMIT) {
                    break;
                }
            }

            $maxVisiblePies = HostListItemDetailed::PIE_CHART_LIMIT - 2;
            $numOfPies = count($pies);
            foreach ($pies as $i => $pie) {
                if (
                    // Show max. 5 elements: if there are more than 5, show 4 + `…`
                    $i > $maxVisiblePies && $numOfPies > HostListItemDetailed::PIE_CHART_LIMIT
                ) {
                    $performanceData->add(new HtmlElement('span', [], '…'));
                    break;
                }

                $performanceData->add(HtmlString::create($pie));
            }
        }

        $footer->add($statusIcons);
        $footer->add($performanceData);
    }

    protected function assembleCaption(BaseHtmlElement $caption)
    {
        $caption->add(CompatPluginOutput::getInstance()->render(
            $this->state->output . "\n" . $this->state->long_output
        ));
    }
}
