<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\ListItemDetailedLayout;
use Icinga\Module\Icingadb\Compat\CompatPluginOutput;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

class HostListItemDetailed extends BaseHostListItem
{
    use ListItemDetailedLayout;

    protected $pie;

    protected function getStateBallSize()
    {
        return StateBall::SIZE_LARGE;
    }

    protected function assembleFooter(BaseHtmlElement $footer)
    {
        $this->pie = HtmlString::create('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" role="img" width="100%" height="100%" viewBox="0 0 1000 1000" preserveAspectRatio="xMinYMin meet" aria-labelledby="aria-title-pl aria-desc-pl">
  <title id="aria-title-pl">pl 0% (0.00%)</title>
  <desc id="aria-desc-pl">pl 0% (0.00%)</desc>
  <g id="root" role="presentation">
    <g x="0" y="0" id="root_inner" transform="translate(0.0, 0.0) scale(1.0, 1.0)">
      <g id="outerGraph">
        <g x="0" y="0" id="outerGraph_inner" transform="translate(15.0, -100.0) scale(1.2, 1.2)">
          <g id="graph">
            <g x="0" y="0" id="graph_inner" transform="translate(0.0, 0.0) scale(1.0, 1.0)">
              <defs>
                <clipPath id="clip">
                  <rect x="1.0" y="0.0" width="1000.0" height="999.0" style="fill: none; stroke: #000;stroke-width: 0;;;"></rect>
                </clipPath>
              </defs>
              <g>
                <path d="M 400.0 100.0 A 400.0 400.0 0 1 1 399.6 100.0" style="fill: #ddccdd; stroke: #000;stroke-width: 1;;;" data-icinga-graph-type="pieslice"></path>
              </g>
              <g></g>
            </g>
          </g>
        </g>
      </g>
    </g>
  </g>
</svg>');

        $statusIcons = Html::tag('div', ['class' => 'status-icons']);

        // ToDo(fs): Get `has_comments` from database
        if ($this->item->comment->limit(1)->execute()->hasResult()) {
            $statusIcons->add(New Icon('comments', ['title' => t('This item has been commented')]));
        }

        if (!$this->item->notifications_enabled) {
            $statusIcons->add(New Icon('bell-slash', ['title' => t('Notifications Disabled')]));
        }

        if (!$this->item->active_checks_enabled) {
            $statusIcons->add(New Icon('eye-slash', ['title' => t('Active checks disabled')]));
        }

        $performanceData = Html::tag('div', ['class' => 'performance-data']);
        if ($this->item->state->performance_data) {
            for ($i = 0; $i < 5; $i++) {
                if ($i > 3) {
                    $performanceData->add(Html::tag('span', [], '…'));
                    break;
                } else {
                    $performanceData->add(Html::tag('div', ['class' => 'inline-pie'], $this->pie));
                }
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
