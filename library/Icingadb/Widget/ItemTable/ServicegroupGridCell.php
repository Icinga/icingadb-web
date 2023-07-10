<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBadge;

class ServicegroupGridCell extends BaseServiceGroupItem
{
    use GridCellLayout;

    protected $defaultAttributes = ['class' => ['group-grid-cell', 'servicegroup-grid-cell']];

    protected function createGroupBadge(): Link
    {
        $url = Url::fromPath('icingadb/services/grid');
        $urlFilter = Filter::all(Filter::equal('servicegroup.name', $this->item->name));

        if ($this->item->services_critical_unhandled > 0) {
            $urlFilter->add(Filter::equal('service.state.soft_state', 2))
                ->add(Filter::equal('service.state.is_handled', 'n'))
                ->add(Filter::equal('service.state.is_reachable', 'y'));

            return new Link(
                new StateBadge($this->item->services_critical_unhandled, 'critical'),
                $url->setQueryString(QueryString::render($urlFilter)),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d service that is currently in CRITICAL state in service group "%s"',
                            'List %d services which are currently in CRITICAL state in service group "%s"',
                            $this->item->services_critical_unhandled
                        ),
                        $this->item->services_critical_unhandled,
                        $this->item->display_name
                    )
                ]
            );
        } elseif ($this->item->services_critical_handled > 0) {
            $urlFilter->add(Filter::equal('service.state.soft_state', 2))
                ->add(Filter::any(
                    Filter::equal('service.state.is_handled', 'y'),
                    Filter::equal('service.state.is_reachable', 'n')
                ));

            return new Link(
                new StateBadge($this->item->services_critical_handled, 'critical', true),
                $url->setQueryString(QueryString::render($urlFilter)),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d service that is currently in CRITICAL (Acknowledged) state in service group'
                            . ' "%s"',
                            'List %d services which are currently in CRITICAL (Acknowledged) state in service group'
                            . ' "%s"',
                            $this->item->services_critical_handled
                        ),
                        $this->item->services_critical_handled,
                        $this->item->display_name
                    )
                ]
            );
        } elseif ($this->item->services_warning_unhandled > 0) {
            $urlFilter->add(Filter::equal('service.state.soft_state', 1))
                ->add(Filter::equal('service.state.is_handled', 'n'))
                ->add(Filter::equal('service.state.is_reachable', 'y'));

            return new Link(
                new StateBadge($this->item->services_warning_unhandled, 'warning'),
                $url->setQueryString(QueryString::render($urlFilter)),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d service that is currently in WARNING state in service group "%s"',
                            'List %d services which are currently in WARNING state in service group "%s"',
                            $this->item->services_warning_unhandled
                        ),
                        $this->item->services_warning_unhandled,
                        $this->item->display_name
                    )
                ]
            );
        } elseif ($this->item->services_warning_handled > 0) {
            $urlFilter->add(Filter::equal('service.state.soft_state', 1))
                ->add(Filter::any(
                    Filter::equal('service.state.is_handled', 'y'),
                    Filter::equal('service.state.is_reachable', 'n')
                ));

            return new Link(
                new StateBadge($this->item->services_warning_handled, 'warning', true),
                $url->setQueryString(QueryString::render($urlFilter)),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d service that is currently in WARNING (Acknowledged) state in service group'
                            . ' "%s"',
                            'List %d services which are currently in WARNING (Acknowledged) state in service group'
                            . ' "%s"',
                            $this->item->services_warning_handled
                        ),
                        $this->item->services_warning_handled,
                        $this->item->display_name
                    )
                ]
            );
        } elseif ($this->item->services_unknown_unhandled > 0) {
            $urlFilter->add(Filter::equal('service.state.soft_state', 3))
                ->add(Filter::equal('service.state.is_handled', 'n'))
                ->add(Filter::equal('service.state.is_reachable', 'y'));

            return new Link(
                new StateBadge($this->item->services_unknown_unhandled, 'unknown'),
                $url->setQueryString(QueryString::render($urlFilter)),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d service that is currently in UNKNOWN state in service group "%s"',
                            'List %d services which are currently in UNKNOWN state in service group "%s"',
                            $this->item->services_unknown_unhandled
                        ),
                        $this->item->services_unknown_unhandled,
                        $this->item->display_name
                    )
                ]
            );
        } elseif ($this->item->services_unknown_handled > 0) {
            $urlFilter->add(Filter::equal('service.state.soft_state', 3))
                ->add(Filter::any(
                    Filter::equal('service.state.is_handled', 'y'),
                    Filter::equal('service.state.is_reachable', 'n')
                ));

            return new Link(
                new StateBadge($this->item->services_unknown_handled, 'unknown', true),
                $url->setQueryString(QueryString::render($urlFilter)),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d service that is currently in UNKNOWN (Acknowledged) state in service group'
                            . ' "%s"',
                            'List %d services which are currently in UNKNOWN (Acknowledged) state in service group'
                            . ' "%s"',
                            $this->item->services_unknown_handled
                        ),
                        $this->item->services_unknown_handled,
                        $this->item->display_name
                    )
                ]
            );
        } elseif ($this->item->services_pending > 0) {
            $urlFilter->add(Filter::equal('service.state.soft_state', 99));

            return new Link(
                new StateBadge($this->item->services_pending, 'pending'),
                $url->setQueryString(QueryString::render($urlFilter)),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d service that is currently in PENDING state in service group "%s"',
                            'List %d services which are currently in PENDING state in service group "%s"',
                            $this->item->services_pending
                        ),
                        $this->item->services_pending,
                        $this->item->display_name
                    )
                ]
            );
        } elseif ($this->item->services_ok > 0) {
            $urlFilter->add(Filter::equal('service.state.soft_state', 0));

            return new Link(
                new StateBadge($this->item->services_ok, 'ok'),
                $url->setQueryString(QueryString::render($urlFilter)),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d service that is currently in OK state in service group "%s"',
                            'List %d services which are currently in OK state in service group "%s"',
                            $this->item->services_ok
                        ),
                        $this->item->services_ok,
                        $this->item->display_name
                    )
                ]
            );
        }

        return new Link(
            new StateBadge(0, 'none'),
            $url,
            [
                'title' => sprintf(
                    $this->translate('There are no services in service group "%s"'),
                    $this->item->display_name
                )
            ]
        );
    }
}
