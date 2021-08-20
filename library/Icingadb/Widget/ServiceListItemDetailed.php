<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\ListItemDetailedLayout;
use Icinga\Module\Icingadb\Util\PerfDataSet;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Text;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

class ServiceListItemDetailed extends BaseServiceListItem
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
        $statusIcons = new HtmlElement('div', Attributes::create(['class' => 'status-icons']));

        // ToDo(fs): Get `has_comments` from database
        if ($this->item->comment->limit(1)->execute()->hasResult()) {
            $statusIcons->addHtml(new Icon('comments', ['title' => t('This item has been commented')]));
        }

        if (! $this->item->notifications_enabled) {
            $statusIcons->addHtml(new Icon('bell-slash', ['title' => t('Notifications disabled')]));
        }

        if (! $this->item->active_checks_enabled) {
            $statusIcons->addHtml(new Icon('eye-slash', ['title' => t('Active checks disabled')]));
        }

        $performanceData = new HtmlElement('div', Attributes::create(['class' => 'performance-data']));
        if ($this->item->state->performance_data) {
            $pieChartData = PerfDataSet::fromString($this->item->state->normalized_performance_data)->asArray();

            $pies = [];
            foreach ($pieChartData as $i => $perfdata) {
                if ($perfdata->isVisualizable()) {
                    $pies[] = $perfdata->asInlinePie()->render();
                }

                // Check if number of visualizable pie charts is larger than PIE_CHART_LIMIT
                if (count($pies) > ServiceListItemDetailed::PIE_CHART_LIMIT) {
                    break;
                }
            }

            $maxVisiblePies = ServiceListItemDetailed::PIE_CHART_LIMIT - 2;
            $numOfPies = count($pies);
            foreach ($pies as $i => $pie) {
                if (
                    // Show max. 5 elements: if there are more than 5, show 4 + `…`
                    $i > $maxVisiblePies && $numOfPies > ServiceListItemDetailed::PIE_CHART_LIMIT
                ) {
                    $performanceData->addHtml(new HtmlElement('span', null, Text::create('…')));
                    break;
                }

                $performanceData->addHtml(HtmlString::create($pie));
            }
        }

        $footer->addHtml($statusIcons);
        $footer->addHtml($performanceData);
    }
}
