<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Chart\Donut;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Forms\Command\Object\ToggleObjectFeaturesForm;
use Icinga\Module\Icingadb\Hook\ExtensionHook\ObjectsDetailExtensionHook;
use Icinga\Module\Icingadb\Model\HoststateSummary;
use Icinga\Module\Icingadb\Model\ServicestateSummary;
use Icinga\Module\Icingadb\Util\FeatureStatus;
use Icinga\Module\Icingadb\Widget\HostStateBadges;
use Icinga\Module\Icingadb\Widget\ServiceStateBadges;
use ipl\Orm\Query;
use ipl\Stdlib\BaseFilter;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\VerticalKeyValue;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Web\Widget\ActionLink;

class ObjectsDetail extends BaseHtmlElement
{
    use BaseFilter;

    protected $summary;

    protected $query;

    protected $type;

    protected $defaultAttributes = ['class' => 'objects-detail'];

    protected $tag = 'div';

    /**
     * Construct an object detail summary widget
     *
     * @param string $type
     * @param HoststateSummary|ServicestateSummary $summary
     * @param Query $query
     */
    public function __construct(string $type, $summary, Query $query)
    {
        $this->summary = $summary;
        $this->query = $query;
        $this->type = $type;
    }

    protected function createChart(): BaseHtmlElement
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
                new VerticalKeyValue(
                    tp('Host', 'Hosts', $this->summary->hosts_total),
                    $this->summary->hosts_total
                ),
                $badges
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
                new VerticalKeyValue(
                    tp('Service', 'Services', $this->summary->services_total),
                    $this->summary->services_total
                ),
                $badges
            ]);
        }

        return $content;
    }

    protected function createComments(): array
    {
        $content = [Html::tag('h2', t('Comments'))];

        if ($this->summary->comments_total > 0) {
            $content[] = new ActionLink(
                sprintf(
                    tp('Show %d comment', 'Show %d comments', $this->summary->comments_total),
                    $this->summary->comments_total
                ),
                Links::comments()->setFilter($this->getBaseFilter())
            );
        } else {
            $content[] = new EmptyState(t('No comments created.'));
        }

        return $content;
    }

    protected function createDowntimes(): array
    {
        $content = [Html::tag('h2', t('Downtimes'))];

        if ($this->summary->downtimes_total > 0) {
            $content[] = new ActionLink(
                sprintf(
                    tp('Show %d downtime', 'Show %d downtimes', $this->summary->downtimes_total),
                    $this->summary->downtimes_total
                ),
                Links::downtimes()->setFilter($this->getBaseFilter())
            );
        } else {
            $content[] = new EmptyState(t('No downtimes scheduled.'));
        }

        return $content;
    }

    protected function createFeatureToggles(): array
    {
        $form = new ToggleObjectFeaturesForm(new FeatureStatus($this->type, $this->summary));

        if ($this->type === 'host') {
            $form->setAction(
                Links::toggleHostsFeatures()
                    ->setFilter($this->getBaseFilter())
                    ->getAbsoluteUrl()
            );
        } else {
            $form->setAction(
                Links::toggleServicesFeatures()
                    ->setFilter($this->getBaseFilter())
                    ->getAbsoluteUrl()
            );
        }

        return [
            Html::tag('h2', t('Feature Commands')),
            $form
        ];
    }

    protected function createExtensions(): array
    {
        return ObjectsDetailExtensionHook::loadExtensions(
            $this->type,
            $this->query,
            $this->getBaseFilter()
        );
    }

    protected function createSummary(): array
    {
        return [
            Html::tag('h2', t('Summary')),
            $this->createChart()
        ];
    }

    protected function assemble()
    {
        $this->add(ObjectsDetailExtensionHook::injectExtensions([
            190 => $this->createSummary(),
            400 => $this->createComments(),
            401 => $this->createDowntimes(),
            701 => $this->createFeatureToggles()
        ], $this->createExtensions()));
    }
}
