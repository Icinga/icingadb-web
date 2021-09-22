<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\ListItemDetailedLayout;
use Icinga\Module\Icingadb\Util\PerfDataSet;
use Icinga\Module\Icingadb\Widget\ItemList\CommentList;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Text;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

class HostListItemDetailed extends BaseHostListItem
{
    use ListItemDetailedLayout;

    /** @var int Max pie charts to be shown */
    const PIE_CHART_LIMIT = 5;

    protected function getStateBallSize(): string
    {
        return StateBall::SIZE_LARGE;
    }

    protected function assembleFooter(BaseHtmlElement $footer)
    {
        $statusIcons = new HtmlElement('div', Attributes::create(['class' => 'status-icons']));

        if ($this->item->state->last_comment_id !== null) {
            $comment = $this->item->state->last_comment;
            $comment->host = $this->item;
            $comment = (new CommentList([$comment]))
                ->setNoSubjectLink()
                ->setObjectLinkDisabled()
                ->setDetailActionsDisabled();

            $statusIcons->addHtml(
                new HtmlElement(
                    'div',
                    Attributes::create(['class' => 'comment-wrapper']),
                    new HtmlElement('div', Attributes::create(['class' => 'comment-popup']), $comment),
                    (new Icon('comments', ['class' => 'comment-icon']))
                )
            );
        }

        if ($this->item->state->is_flapping) {
            $statusIcons->addHtml(new Icon(
                'random',
                [
                    'title' => sprintf(t('Host "%s" is in flapping state'), $this->item->display_name),
                ]
            ));
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
