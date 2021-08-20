<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Chart\Donut;
use Icinga\Data\Filter\Filter;
use Icinga\Module\Icingadb\Common\BaseFilter;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\HoststateSummary;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\TemplateString;
use ipl\Html\Text;
use ipl\Web\Common\Card;
use ipl\Web\Filter\QueryString;

class HostSummaryDonut extends Card
{
    use BaseFilter;

    protected $defaultAttributes = ['class' => 'donut-container', 'data-base-target' => '_next'];

    /** @var HoststateSummary */
    protected $summary;

    public function __construct(HoststateSummary $summary)
    {
        $this->summary = $summary;
    }

    protected function assembleBody(BaseHtmlElement $body)
    {
        $donut = (new Donut())
            ->addSlice($this->summary->hosts_up, ['class' => 'slice-state-ok'])
            ->addSlice($this->summary->hosts_down_handled, ['class' => 'slice-state-critical-handled'])
            ->addSlice($this->summary->hosts_down_unhandled, ['class' => 'slice-state-critical'])
            ->addSlice($this->summary->hosts_unreachable_handled, ['class' => 'slice-state-unreachable-handled'])
            ->addSlice($this->summary->hosts_unreachable_unhandled, ['class' => 'slice-state-unreachable'])
            ->addSlice($this->summary->hosts_pending, ['class' => 'slice-state-pending'])
            ->setLabelBig($this->summary->hosts_down_unhandled)
            ->setLabelBigUrl(Links::hosts()->addFilter(
                Filter::fromQueryString(QueryString::render($this->getBaseFilter()))
            )->addParams([
                'host.state.soft_state' => 1,
                'host.state.is_handled' => 'n',
                'sort' => 'host.state.last_state_change'
            ]))
            ->setLabelBigEyeCatching($this->summary->hosts_down_unhandled > 0)
            ->setLabelSmall(t('Down'));

        $body->addHtml(
            new HtmlElement('div', Attributes::create(['class' => 'donut']), new HtmlString($donut->render()))
        );
    }

    protected function assembleFooter(BaseHtmlElement $footer)
    {
        $footer->addHtml((new HostStateBadges($this->summary))->setBaseFilter($this->getBaseFilter()));
    }

    protected function assembleHeader(BaseHtmlElement $header)
    {
        $header->addHtml(
            new HtmlElement('h2', null, Text::create(t('Hosts'))),
            new HtmlElement('span', Attributes::create(['class' => 'meta']), TemplateString::create(
                t('{{#total}}Total{{/total}} %d'),
                ['total' => new HtmlElement('span')],
                (int) $this->summary->hosts_total
            ))
        );
    }
}
