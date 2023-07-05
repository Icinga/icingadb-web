<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Chart\Donut;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\TemplateString;
use ipl\Html\Text;
use ipl\Stdlib\BaseFilter;
use ipl\Stdlib\Filter;
use ipl\Web\Common\Card;
use ipl\Web\Filter\QueryString;

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
        $labelBigUrlFilter = Filter::all(
            Filter::equal('service.state.soft_state', 2),
            Filter::equal('service.state.is_handled', 'n')
        );
        if ($this->hasBaseFilter()) {
            $labelBigUrlFilter->add($this->getBaseFilter());
        }

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
            ->setLabelBigUrl(Links::services()->setQueryString(QueryString::render($labelBigUrlFilter))->addParams([
                'sort' => 'service.state.last_state_change'
            ]))
            ->setLabelBigEyeCatching($this->summary->services_critical_unhandled > 0)
            ->setLabelSmall(t('Critical'));

        $body->addHtml(
            new HtmlElement('div', Attributes::create(['class' => 'donut']), new HtmlString($donut->render()))
        );
    }

    protected function assembleFooter(BaseHtmlElement $footer)
    {
        $footer->addHtml((new ServiceStateBadges($this->summary))->setBaseFilter($this->getBaseFilter()));
    }

    protected function assembleHeader(BaseHtmlElement $header)
    {
        $header->addHtml(
            new HtmlElement('h2', null, Text::create(t('Services'))),
            new HtmlElement('span', Attributes::create(['class' => 'meta']), TemplateString::create(
                t('{{#total}}Total{{/total}} %d'),
                ['total' => new HtmlElement('span')],
                (int) $this->summary->services_total
            ))
        );
    }
}
