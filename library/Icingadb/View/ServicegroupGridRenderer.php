<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\View;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\ServicegroupSummary;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\BaseFilter;
use ipl\Stdlib\Filter;
use ipl\Web\Common\ItemRenderer;
use ipl\Web\Url;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBadge;

/** @implements ItemRenderer<ServicegroupSummary> */
class ServicegroupGridRenderer implements ItemRenderer
{
    use Translation;
    use BaseFilter;

    public function assembleAttributes($item, Attributes $attributes, string $layout): void
    {
        $attributes->get('class')->addValue(['group-grid-cell', 'servicegroup']);
    }

    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        $url = Url::fromPath('icingadb/services/grid');
        $urlFilter = Filter::all(Filter::equal('servicegroup.name', $item->name));

        if ($item->services_critical_unhandled > 0) {
            $urlFilter->add(Filter::equal('service.state.soft_state', 2))
                ->add(Filter::equal('service.state.is_handled', 'n'))
                ->add(Filter::equal('service.state.is_reachable', 'y'));

            $link = new Link(
                new StateBadge($item->services_critical_unhandled, 'critical'),
                $url->setFilter($urlFilter),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d service that is currently in CRITICAL state in service group "%s"',
                            'List %d services which are currently in CRITICAL state in service group "%s"',
                            $item->services_critical_unhandled
                        ),
                        $item->services_critical_unhandled,
                        $item->display_name
                    )
                ]
            );
        } elseif ($item->services_critical_handled > 0) {
            $urlFilter->add(Filter::equal('service.state.soft_state', 2))
                ->add(Filter::any(
                    Filter::equal('service.state.is_handled', 'y'),
                    Filter::equal('service.state.is_reachable', 'n')
                ));

            $link = new Link(
                new StateBadge($item->services_critical_handled, 'critical', true),
                $url->setFilter($urlFilter),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d service that is currently in CRITICAL (Acknowledged) state in service group'
                            . ' "%s"',
                            'List %d services which are currently in CRITICAL (Acknowledged) state in service group'
                            . ' "%s"',
                            $item->services_critical_handled
                        ),
                        $item->services_critical_handled,
                        $item->display_name
                    )
                ]
            );
        } elseif ($item->services_warning_unhandled > 0) {
            $urlFilter->add(Filter::equal('service.state.soft_state', 1))
                ->add(Filter::equal('service.state.is_handled', 'n'))
                ->add(Filter::equal('service.state.is_reachable', 'y'));

            $link = new Link(
                new StateBadge($item->services_warning_unhandled, 'warning'),
                $url->setFilter($urlFilter),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d service that is currently in WARNING state in service group "%s"',
                            'List %d services which are currently in WARNING state in service group "%s"',
                            $item->services_warning_unhandled
                        ),
                        $item->services_warning_unhandled,
                        $item->display_name
                    )
                ]
            );
        } elseif ($item->services_warning_handled > 0) {
            $urlFilter->add(Filter::equal('service.state.soft_state', 1))
                ->add(Filter::any(
                    Filter::equal('service.state.is_handled', 'y'),
                    Filter::equal('service.state.is_reachable', 'n')
                ));

            $link = new Link(
                new StateBadge($item->services_warning_handled, 'warning', true),
                $url->setFilter($urlFilter),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d service that is currently in WARNING (Acknowledged) state in service group'
                            . ' "%s"',
                            'List %d services which are currently in WARNING (Acknowledged) state in service group'
                            . ' "%s"',
                            $item->services_warning_handled
                        ),
                        $item->services_warning_handled,
                        $item->display_name
                    )
                ]
            );
        } elseif ($item->services_unknown_unhandled > 0) {
            $urlFilter->add(Filter::equal('service.state.soft_state', 3))
                ->add(Filter::equal('service.state.is_handled', 'n'))
                ->add(Filter::equal('service.state.is_reachable', 'y'));

            $link = new Link(
                new StateBadge($item->services_unknown_unhandled, 'unknown'),
                $url->setFilter($urlFilter),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d service that is currently in UNKNOWN state in service group "%s"',
                            'List %d services which are currently in UNKNOWN state in service group "%s"',
                            $item->services_unknown_unhandled
                        ),
                        $item->services_unknown_unhandled,
                        $item->display_name
                    )
                ]
            );
        } elseif ($item->services_unknown_handled > 0) {
            $urlFilter->add(Filter::equal('service.state.soft_state', 3))
                ->add(Filter::any(
                    Filter::equal('service.state.is_handled', 'y'),
                    Filter::equal('service.state.is_reachable', 'n')
                ));

            $link = new Link(
                new StateBadge($item->services_unknown_handled, 'unknown', true),
                $url->setFilter($urlFilter),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d service that is currently in UNKNOWN (Acknowledged) state in service group'
                            . ' "%s"',
                            'List %d services which are currently in UNKNOWN (Acknowledged) state in service group'
                            . ' "%s"',
                            $item->services_unknown_handled
                        ),
                        $item->services_unknown_handled,
                        $item->display_name
                    )
                ]
            );
        } elseif ($item->services_pending > 0) {
            $urlFilter->add(Filter::equal('service.state.soft_state', 99));

            $link = new Link(
                new StateBadge($item->services_pending, 'pending'),
                $url->setFilter($urlFilter),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d service that is currently in PENDING state in service group "%s"',
                            'List %d services which are currently in PENDING state in service group "%s"',
                            $item->services_pending
                        ),
                        $item->services_pending,
                        $item->display_name
                    )
                ]
            );
        } elseif ($item->services_ok > 0) {
            $urlFilter->add(Filter::equal('service.state.soft_state', 0));

            $link = new Link(
                new StateBadge($item->services_ok, 'ok'),
                $url->setFilter($urlFilter),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d service that is currently in OK state in service group "%s"',
                            'List %d services which are currently in OK state in service group "%s"',
                            $item->services_ok
                        ),
                        $item->services_ok,
                        $item->display_name
                    )
                ]
            );
        } else {
            $link = new Link(
                new StateBadge(0, 'none'),
                $url,
                [
                    'title' => sprintf(
                        $this->translate('There are no services in service group "%s"'),
                        $item->display_name
                    )
                ]
            );
        }

        $visual->addHtml($link);
    }

    public function assembleTitle($item, HtmlDocument $title, string $layout): void
    {
        $link = new Link(
            $item->display_name,
            Links::servicegroup($item),
            [
                'class' => 'subject',
                'title' => sprintf(
                    $this->translate('List all services in the group "%s"'),
                    $item->display_name
                )
            ]
        );

        if ($this->hasBaseFilter()) {
            $link->getUrl()->setFilter($this->getBaseFilter());
        }

        $title->addHtml($link);
    }

    public function assembleCaption($item, HtmlDocument $caption, string $layout): void
    {
        $caption->addHtml(new HtmlElement('span', null, Text::create($item->name)));
    }

    public function assembleExtendedInfo($item, HtmlDocument $info, string $layout): void
    {
    }

    public function assembleFooter($item, HtmlDocument $footer, string $layout): void
    {
    }

    public function assemble($item, string $name, HtmlDocument $element, string $layout): bool
    {
        return false; // no custom sections
    }
}
