<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Chart\Donut;
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
        $servicestateSummary = ServicestateSummary::on($db)->with('state');

        yield $this->export($hoststateSummary, $servicestateSummary);

        $hoststateSummary = $hoststateSummary->first();
        $servicestateSummary = $servicestateSummary->first();

        $hostsChart = (new Donut())
            ->addSlice($hoststateSummary->hosts_up, ['class' => 'slice-state-ok'])
            ->addSlice($hoststateSummary->hosts_down_handled, ['class' => 'slice-state-critical-handled'])
            ->addSlice($hoststateSummary->hosts_down_unhandled, ['class' => 'slice-state-critical'])
            ->addSlice($hoststateSummary->hosts_pending, ['class' => 'slice-state-pending'])
            ->setLabelBig($hoststateSummary->hosts_down_unhandled)
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
            ->setLabelBigEyeCatching($servicestateSummary->services_critical_unhandled > 0)
            ->setLabelSmall($this->translate('Services Critical'));

        $this->addContent(Html::tag('section', ['class' => 'donut-container'], [
                Html::tag('h2', 'Host Summary'),
                Html::tag('div', ['class' => 'donut'], new HtmlString($hostsChart->render())),
                new HostStateBadges($hoststateSummary)
        ]));

        $this->addContent(Html::tag('section', ['class' => 'donut-container'], [
                Html::tag('h2', 'Service Summary'),
                Html::tag('div', ['class' => 'donut'], new HtmlString($servicesChart->render())),
                new ServiceStateBadges($servicestateSummary)
        ]));
    }
}
