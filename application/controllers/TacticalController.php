<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Chart\Donut;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\HoststateSummary;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\HostStateBadges;
use Icinga\Module\Icingadb\Widget\ServiceStateBadges;
use ipl\Html\Html;
use ipl\Html\HtmlString;

class TacticalController extends Controller
{
    public function indexAction()
    {
        $this->setTitle($this->translate('Tactical Overview'));

        $db = $this->getDb();

        $hoststateSummary = HoststateSummary::on($db)->with('state');
        // With relation `host` because otherwise the filter editor only presents service cols
        $servicestateSummary = ServicestateSummary::on($db)->with(['state', 'host']);

        $filterControl = $this->createFilterControl($servicestateSummary);
        $this->createFilterControl($hoststateSummary);

        $this->filter($hoststateSummary);
        $this->filter($servicestateSummary);

        yield $this->export($hoststateSummary, $servicestateSummary);

        $this->addControl($filterControl);

        $hoststateSummary = $hoststateSummary->first();
        $servicestateSummary = $servicestateSummary->first();

        $hostsChart = (new Donut())
            ->addSlice($hoststateSummary->hosts_up, ['class' => 'slice-state-ok'])
            ->addSlice($hoststateSummary->hosts_down_handled, ['class' => 'slice-state-critical-handled'])
            ->addSlice($hoststateSummary->hosts_down_unhandled, ['class' => 'slice-state-critical'])
            ->addSlice($hoststateSummary->hosts_unreachable_handled, ['class' => 'slice-state-unreachable-handled'])
            ->addSlice($hoststateSummary->hosts_unreachable_unhandled, ['class' => 'slice-state-unreachable'])
            ->addSlice($hoststateSummary->hosts_pending, ['class' => 'slice-state-pending'])
            ->setLabelBig($hoststateSummary->hosts_down_unhandled)
            ->setLabelBigUrl(Links::hosts()->addFilter($this->getFilter())->addParams([
                'host.state.soft_state' => 1,
                'host.state.is_handled' => 'n',
                'sort' => 'host.state.last_state_change'
            ]))
            ->setLabelBigEyeCatching($hoststateSummary->hosts_down_unhandled > 0)
            ->setLabelSmall($this->translate('Hosts Down'));

        $servicesChart = (new Donut())
            ->addSlice($servicestateSummary->services_ok, ['class' => 'slice-state-ok'])
            ->addSlice($servicestateSummary->services_warning_handled, ['class' => 'slice-state-warning-handled'])
            ->addSlice($servicestateSummary->services_warning_unhandled, ['class' => 'slice-state-warning'])
            ->addSlice($servicestateSummary->services_critical_handled, ['class' => 'slice-state-critical-handled'])
            ->addSlice($servicestateSummary->services_critical_unhandled, ['class' => 'slice-state-critical'])
            ->addSlice($servicestateSummary->services_unknown_handled, ['class' => 'slice-state-unknown-handled'])
            ->addSlice($servicestateSummary->services_unknown_unhandled, ['class' => 'slice-state-unknown'])
            ->addSlice($servicestateSummary->services_pending, ['class' => 'slice-state-pending'])
            ->setLabelBig($servicestateSummary->services_critical_unhandled)
            ->setLabelBigUrl(Links::services()->addFilter($this->getFilter())->addParams([
                'service.state.soft_state' => 2,
                'service.state.is_handled' => 'n',
                'sort' => 'service.state.last_state_change'
            ]))
            ->setLabelBigEyeCatching($servicestateSummary->services_critical_unhandled > 0)
            ->setLabelSmall($this->translate('Services Critical'));

        $this->addContent(Html::tag('section', ['class' => 'donut-container', 'data-base-target' => '_next'], [
            Html::tag('h2', 'Host Summary'),
            Html::tag('div', ['class' => 'donut'], new HtmlString($hostsChart->render())),
            (new HostStateBadges($hoststateSummary))->setBaseFilter($this->getFilter())
        ]));

        $this->addContent(Html::tag('section', ['class' => 'donut-container', 'data-base-target' => '_next'], [
            Html::tag('h2', 'Service Summary'),
            Html::tag('div', ['class' => 'donut'], new HtmlString($servicesChart->render())),
            (new ServiceStateBadges($servicestateSummary))->setBaseFilter($this->getFilter())
        ]));

        $this->setAutorefreshInterval(10);
    }
}
