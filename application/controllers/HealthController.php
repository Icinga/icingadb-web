<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Compat\CompatBackend;
use Icinga\Module\Icingadb\Model\HoststateSummary;
use Icinga\Module\Icingadb\Model\Instance;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Health;
use Icinga\Module\Icingadb\Widget\VerticalKeyValue;
use Icinga\Module\Monitoring\Forms\Command\Instance\ToggleInstanceFeaturesCommandForm;
use ipl\Html\Html;
use ipl\Html\HtmlString;

class HealthController extends Controller
{
    public function indexAction()
    {
        $this->setTitle($this->translate('Health'));

        $db = $this->getDb();

        $instance = Instance::on($db)->with(['endpoint']);
        $hoststateSummary = HoststateSummary::on($db)->with('state');
        $servicestateSummary = ServicestateSummary::on($db)->with('state');

        $this->applyMonitoringRestriction($hoststateSummary);
        $this->applyMonitoringRestriction($servicestateSummary);

        yield $this->export($instance, $hoststateSummary, $servicestateSummary);

        $instance = $instance->first();

        if ($instance === null) {
            $this->addContent(Html::tag('p', $this->translate(
                'It seems that Icinga DB is not running.'
                . ' Make sure Icinga DB is running and writing into the database.'
            )));

            return;
        }

        $hoststateSummary = $hoststateSummary->first();
        $servicestateSummary = $servicestateSummary->first();

        $this->content->addAttributes(['class' => 'monitoring-health']);

        $this->addContent(new Health($instance));
        $this->addContent(Html::tag('section', ['class' => 'check-summary'], [
                Html::tag('div', ['class' => 'col'], [
                    Html::tag('h3', $this->translate('Host Checks')),
                    Html::tag('div', ['class' => 'col-content'], [
                        new VerticalKeyValue(
                            $this->translate('Active'),
                            $hoststateSummary->hosts_active_checks_enabled
                        ),
                        new VerticalKeyValue(
                            $this->translate('Passive'),
                            $hoststateSummary->hosts_passive_checks_enabled
                        )
                    ])
                ]),
                Html::tag('div', ['class' => 'col'], [
                    Html::tag('h3', $this->translate('Service Checks')),
                    Html::tag('div', ['class' => 'col-content'], [
                        new VerticalKeyValue(
                            $this->translate('Active'),
                            $servicestateSummary->services_active_checks_enabled
                        ),
                        new VerticalKeyValue(
                            $this->translate('Passive'),
                            $servicestateSummary->services_passive_checks_enabled
                        )
                    ])
                ])
        ]));

        $featureCommands = Html::tag(
            'section',
            ['class' => 'instance-commands'],
            Html::tag('h2', $this->translate('Feature Commands'))
        );
        $programStatus = (object) [
            'active_host_checks_enabled'    => $instance->icinga2_active_host_checks_enabled,
            'active_service_checks_enabled' => $instance->icinga2_active_service_checks_enabled,
            'event_handlers_enabled'        => $instance->icinga2_event_handlers_enabled,
            'flap_detection_enabled'        => $instance->icinga2_flap_detection_enabled,
            'notifications_enabled'         => $instance->icinga2_notifications_enabled,
            'process_performance_data'      => $instance->icinga2_performance_data_enabled,
            'program_version'               => $instance->icinga2_version
        ];
        $toggleInstanceFeaturesCommandForm = new ToggleInstanceFeaturesCommandForm();
        $toggleInstanceFeaturesCommandForm
            ->setBackend(new CompatBackend())
            ->setStatus($programStatus)
            ->load($programStatus)
            ->handleRequest();
        $featureCommands->add(HtmlString::create($toggleInstanceFeaturesCommandForm->render()));
        $this->addContent($featureCommands);

        $this->setAutorefreshInterval(30);
    }
}
