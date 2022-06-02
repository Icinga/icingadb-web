<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Command\Instance\ToggleInstanceFeatureCommand;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Forms\Command\Instance\ToggleInstanceFeaturesForm;
use Icinga\Module\Icingadb\Model\HoststateSummary;
use Icinga\Module\Icingadb\Model\Instance;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Health;
use ipl\Web\Widget\VerticalKeyValue;
use ipl\Html\Html;
use ipl\Web\Url;

class HealthController extends Controller
{
    public function indexAction()
    {
        $this->addTitleTab(t('Health'));

        $db = $this->getDb();

        $instance = Instance::on($db)->with(['endpoint']);
        $hoststateSummary = HoststateSummary::on($db)->with('state');
        $servicestateSummary = ServicestateSummary::on($db)->with('state');

        $this->applyRestrictions($hoststateSummary);
        $this->applyRestrictions($servicestateSummary);

        yield $this->export($instance, $hoststateSummary, $servicestateSummary);

        $instance = $instance->first();

        if ($instance === null) {
            $this->addContent(Html::tag('p', t(
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
                    Html::tag('h3', t('Host Checks')),
                    Html::tag('div', ['class' => 'col-content'], [
                        new VerticalKeyValue(
                            t('Active'),
                            $hoststateSummary->hosts_active_checks_enabled
                        ),
                        new VerticalKeyValue(
                            t('Passive'),
                            $hoststateSummary->hosts_passive_checks_enabled
                        )
                    ])
                ]),
                Html::tag('div', ['class' => 'col'], [
                    Html::tag('h3', t('Service Checks')),
                    Html::tag('div', ['class' => 'col-content'], [
                        new VerticalKeyValue(
                            t('Active'),
                            $servicestateSummary->services_active_checks_enabled
                        ),
                        new VerticalKeyValue(
                            t('Passive'),
                            $servicestateSummary->services_passive_checks_enabled
                        )
                    ])
                ])
        ]));

        $featureCommands = Html::tag(
            'section',
            ['class' => 'instance-commands'],
            Html::tag('h2', t('Feature Commands'))
        );
        $toggleInstanceFeaturesCommandForm = new ToggleInstanceFeaturesForm([
            ToggleInstanceFeatureCommand::FEATURE_ACTIVE_HOST_CHECKS =>
                $instance->icinga2_active_host_checks_enabled,
            ToggleInstanceFeatureCommand::FEATURE_ACTIVE_SERVICE_CHECKS =>
                $instance->icinga2_active_service_checks_enabled,
            ToggleInstanceFeatureCommand::FEATURE_EVENT_HANDLERS =>
                $instance->icinga2_event_handlers_enabled,
            ToggleInstanceFeatureCommand::FEATURE_FLAP_DETECTION =>
                $instance->icinga2_flap_detection_enabled,
            ToggleInstanceFeatureCommand::FEATURE_NOTIFICATIONS =>
                $instance->icinga2_notifications_enabled,
            ToggleInstanceFeatureCommand::FEATURE_PERFORMANCE_DATA =>
                $instance->icinga2_performance_data_enabled
        ]);
        $toggleInstanceFeaturesCommandForm->setObjects([$instance]);
        $toggleInstanceFeaturesCommandForm->on(ToggleInstanceFeaturesForm::ON_SUCCESS, function () {
            $this->getResponse()->setAutoRefreshInterval(1);

            $this->redirectNow(Url::fromPath('icingadb/health')->getAbsoluteUrl());
        });
        $toggleInstanceFeaturesCommandForm->handleRequest(ServerRequest::fromGlobals());

        $featureCommands->add($toggleInstanceFeaturesCommandForm);
        $this->addContent($featureCommands);

        $this->setAutorefreshInterval(30);
    }
}
