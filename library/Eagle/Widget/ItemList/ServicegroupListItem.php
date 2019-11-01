<?php

namespace Icinga\Module\Eagle\Widget\ItemList;

use Icinga\Chart\Donut;
use Icinga\Module\Eagle\Common\BaseTableRowItem;
use Icinga\Module\Eagle\Widget\ServiceStateBadges;
use Icinga\Module\Eagle\Widget\VerticalKeyValue;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlString;

class ServicegroupListItem extends BaseTableRowItem
{
    protected function assembleColumns(HtmlDocument $columns)
    {
        $servicesChart = (new Donut())
            ->addSlice($this->item->services_ok, ['class' => 'slice-state-ok'])
            ->addSlice($this->item->services_warning_handled, ['class' => 'slice-state-warning-handled'])
            ->addSlice($this->item->services_warning_unhandled, ['class' => 'slice-state-warning'])
            ->addSlice($this->item->services_critical_handled, ['class' => 'slice-state-critical-handled'])
            ->addSlice($this->item->services_critical_unhandled, ['class' => 'slice-state-critical'])
            ->addSlice($this->item->services_unknown_handled, ['class' => 'slice-state-unknown-handled'])
            ->addSlice($this->item->services_unknown_unhandled, ['class' => 'slice-state-unknown'])
            ->addSlice($this->item->services_pending, ['class' => 'slice-state-pending']);

        if ($this->item->services_total > 0) {
            $columns->add([
                $this->createColumn(HtmlString::create($servicesChart->render())),
                $this->createColumn(new VerticalKeyValue(
                    'Service' . ($this->item->services_total > 1 ? 's' : ''), $this->item->services_total
                ))->addAttributes(['class' => 'text-center']),
                $this->createColumn(new ServiceStateBadges($this->item))
            ]);
        }
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
        $title->add([
            $this->item->display_name,
            Html::tag('br'),
            $this->item->name
        ]);
    }
}
