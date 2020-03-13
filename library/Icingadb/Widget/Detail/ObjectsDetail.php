<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Chart\Donut;
use Icinga\Module\Icingadb\Common\BaseFilter;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Compat\CompatBackend;
use Icinga\Module\Icingadb\Compat\FeatureStatus;
use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Module\Icingadb\Widget\HostStateBadges;
use Icinga\Module\Icingadb\Widget\ServiceStateBadges;
use Icinga\Module\Icingadb\Widget\VerticalKeyValue;
use Icinga\Module\Monitoring\Forms\Command\Object\ToggleObjectFeaturesCommandForm;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Web\Widget\ActionLink;

class ObjectsDetail extends BaseHtmlElement
{
    use BaseFilter;

    protected $summary;

    protected $type;

    protected $defaultAttributes = ['class' => 'objects-detail'];

    protected $tag = 'div';

    public function __construct($type, $summary)
    {
        $this->summary = $summary;
        $this->type = $type;
    }

    protected function createChart()
    {
        $content = Html::tag('div', ['class' => 'multiselect-summary']);

        if ($this->type === 'host') {
            $hostsChart = (new Donut())
                ->addSlice($this->summary->hosts_up, ['class' => 'slice-state-ok'])
                ->addSlice($this->summary->hosts_down_handled, ['class' => 'slice-state-critical-handled'])
                ->addSlice($this->summary->hosts_down_unhandled, ['class' => 'slice-state-critical'])
                ->addSlice($this->summary->hosts_pending, ['class' => 'slice-state-pending']);

            $badges = (new HostStateBadges($this->summary))
                ->setBaseFilter($this->getBaseFilter());

            $content->add([
                HtmlString::create($hostsChart->render()),
                new VerticalKeyValue('Hosts', $this->summary->hosts_total),
                new HostStateBadges($badges)
            ]);
        } else {
            $servicesChart = (new Donut())
                ->addSlice($this->summary->services_ok, ['class' => 'slice-state-ok'])
                ->addSlice($this->summary->services_warning_handled, ['class' => 'slice-state-warning-handled'])
                ->addSlice($this->summary->services_warning_unhandled, ['class' => 'slice-state-warning'])
                ->addSlice($this->summary->services_critical_handled, ['class' => 'slice-state-critical-handled'])
                ->addSlice($this->summary->services_critical_unhandled, ['class' => 'slice-state-critical'])
                ->addSlice($this->summary->services_unknown_handled, ['class' => 'slice-state-unknown-handled'])
                ->addSlice($this->summary->services_unknown_unhandled, ['class' => 'slice-state-unknown'])
                ->addSlice($this->summary->services_pending, ['class' => 'slice-state-pending']);

            $badges = (new ServiceStateBadges($this->summary))
                ->setBaseFilter($this->getBaseFilter());

            $content->add([
                HtmlString::create($servicesChart->render()),
                new VerticalKeyValue('Services', $this->summary->services_total),
                $badges
            ]);
        }

        return $content;
    }

    protected function createComments()
    {
        $content = [Html::tag('h2', 'Comments')];

        if ($this->summary->comments_total > 0) {
            $content[] = new ActionLink(
                sprintf('Show %d comments', $this->summary->comments_total),
                Links::comments()->setQueryString($this->getBaseFilter()->toQueryString())
            );
        } else {
            $content[] = new EmptyState('No comments created.');
        }

        return $content;
    }

    protected function createDowntimes()
    {
        $content = [Html::tag('h2', 'Downtimes')];

        if ($this->summary->downtimes_total > 0) {
            $content[] = new ActionLink(
                sprintf('Show %d downtimes', $this->summary->downtimes_total),
                Links::downtimes()->setQueryString($this->getBaseFilter()->toQueryString())
            );
        } else {
            $content[] = new EmptyState('No downtimes scheduled.');
        }

        return $content;
    }

    protected function createFeatureToggles()
    {
        $form = new ToggleObjectFeaturesCommandForm([
            'backend' => new CompatBackend()
        ]);

        $form->load(new FeatureStatus($this->type, $this->summary));

        if ($this->type === 'host') {
            $form->setAction(Links::toggleHostsFeatures()->setQueryString($this->getBaseFilter()->toQueryString()));
        } else {
            $form->setAction(Links::toggleServicesFeatures()->setQueryString($this->getBaseFilter()->toQueryString()));
        }

        return [
            Html::tag('h2', 'Feature Commands'),
            HtmlString::create($form->render())
        ];
    }

    protected function createSummary()
    {
        return [
            Html::tag('h2', 'Summary'),
            $this->createChart()
        ];
    }

    protected function assemble()
    {
        $this->add([
            $this->createSummary(),
            $this->createComments(),
            $this->createDowntimes(),
            $this->createFeatureToggles()
        ]);
    }
}
