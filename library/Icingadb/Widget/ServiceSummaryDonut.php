<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Chart\Donut;
use Icinga\Module\Icingadb\Common\BaseFilter;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;

class ServiceSummaryDonut extends Card
{
    use BaseFilter;

    protected $defaultAttributes = ['class' => 'donut-container', 'data-base-target' => '_next'];

    /** @var ServicestateSummary */
    protected $summary;

    public function __construct(ServicestateSummary $summary)
    {
        $this->summary = $summary;
    }

    protected function assembleBody(BaseHtmlElement $body)
    {
        $donut = (new Donut())
            ->addSlice($this->summary->services_ok, ['class' => 'slice-state-ok'])
            ->addSlice($this->summary->services_warning_handled, ['class' => 'slice-state-warning-handled'])
            ->addSlice($this->summary->services_warning_unhandled, ['class' => 'slice-state-warning'])
            ->addSlice($this->summary->services_critical_handled, ['class' => 'slice-state-critical-handled'])
            ->addSlice($this->summary->services_critical_unhandled, ['class' => 'slice-state-critical'])
            ->addSlice($this->summary->services_unknown_handled, ['class' => 'slice-state-unknown-handled'])
            ->addSlice($this->summary->services_unknown_unhandled, ['class' => 'slice-state-unknown'])
            ->addSlice($this->summary->services_pending, ['class' => 'slice-state-pending'])
            ->setLabelBig($this->summary->services_critical_unhandled)
            ->setLabelBigUrl(Links::services()->addFilter($this->getBaseFilter())->addParams([
                'service.state.soft_state' => 2,
                'service.state.is_handled' => 'n',
                'sort' => 'service.state.last_state_change'
            ]))
            ->setLabelBigEyeCatching($this->summary->services_critical_unhandled > 0)
            ->setLabelSmall(
                tp('Service Critical', 'Services Critical', $this->summary->services_critical_unhandled)
            );

        $body->add(new HtmlElement('div', ['class' => 'donut'], new HtmlString($donut->render())));
    }

    protected function assembleFooter(BaseHtmlElement $footer)
    {
        $footer->add((new ServiceStateBadges($this->summary))->setBaseFilter($this->getBaseFilter()));
    }

    protected function assembleHeader(BaseHtmlElement $header)
    {
        $header->add([
            new HtmlElement('h2', null, t('Service Summary')),
            Html::tag('span', ['class' => 'meta'], [
                Html::tag('span', t('Total')),
                ' ' . $this->summary->services_total
            ])
        ]);
    }
}
