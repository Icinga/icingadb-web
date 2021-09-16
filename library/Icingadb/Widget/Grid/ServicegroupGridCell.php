<?php

namespace Icinga\Module\Icingadb\Widget\Grid;

use Icinga\Data\Filter\Filter;
use Icinga\Module\Icingadb\Common\GroupGridCell;
use ipl\Web\Url;
use ipl\Web\Widget\Link;

class ServicegroupGridCell extends GroupGridCell
{
    protected function init()
    {
        $this->url = Url::fromPath('icingadb/services/grid')->addParams(['servicegroup.name' => $this->item->name]);

        parent::init();
    }

    protected function assembleLabel()
    {
        $this->add(new Link($this->item->display_name, $this->url->onlyWith('servicegroup.name'), [
            'title' => sprintf(t('List all services in the group "%s"'), $this->item->display_name)
        ]));
    }

    protected function assembleContent()
    {
        if ($this->item->services_critical_unhandled > 0) {
            $this->stateAssembled = true;
            $this->add(new Link(
                $this->item->services_critical_unhandled,
                $this->url->addFilter(Filter::matchAll(
                    Filter::where('state.soft_state', 2),
                    Filter::where('state.is_handled', 'n')
                )),
                [
                    'class' => 'state-critical',
                    'title' => sprintf(tp(
                        'List %d service that is currently in CRITICAL state in service group "%s"',
                        'List %d services which are currently in CRITICAL state in service group "%s"',
                        $this->item->services_critical_unhandled
                    ), $this->item->services_critical_unhandled, $this->item->display_name)
                ]
            ));
        } elseif ($this->item->services_critical_handled > 0) {
            $this->stateAssembled = true;
            $this->add(new Link(
                $this->item->services_critical_handled,
                $this->url->addFilter(Filter::matchAll(
                    Filter::where('state.soft_state', 2),
                    Filter::where('state.is_handled', 'y')
                )),
                [
                    'class' => 'state-critical handled',
                    'title' => sprintf(tp(
                        'List %d service that is currently in CRITICAL (Acknowledged) state in service group "%s"',
                        'List %d services which are currently in CRITICAL (Acknowledged) state in service group "%s"',
                        $this->item->services_critical_handled
                    ), $this->item->services_critical_handled, $this->item->display_name)
                ]
            ));
        } elseif ($this->item->services_warning_unhandled > 0) {
            $this->stateAssembled = true;
            $this->add(new Link(
                $this->item->services_warning_unhandled,
                $this->url->addFilter(Filter::matchAll(
                    Filter::where('state.soft_state', 1),
                    Filter::where('state.is_handled', 'n')
                )),
                [
                    'class' => 'state-warning',
                    'title' => sprintf(tp(
                        'List %d service that is currently in WARNING state in service group "%s"',
                        'List %d services which are currently in WARNING state in service group "%s"',
                        $this->item->services_warning_unhandled
                    ), $this->item->services_warning_unhandled, $this->item->display_name)
                ]
            ));
        } elseif ($this->item->services_warning_handled > 0) {
            $this->stateAssembled = true;
            $this->add(new Link(
                $this->item->services_warning_handled,
                $this->url->addFilter(Filter::matchAll(
                    Filter::where('state.soft_state', 1),
                    Filter::where('state.is_handled', 'y')
                )),
                [
                    'class' => 'state-warning handled',
                    'title' => sprintf(tp(
                        'List %d service that is currently in WARNING (Acknowledged) state in service group "%s"',
                        'List %d services which are currently in WARNING (Acknowledged) state in service group "%s"',
                        $this->item->services_warning_handled
                    ), $this->item->services_warning_handled, $this->item->display_name)
                ]
            ));
        } elseif ($this->item->services_unknown_unhandled > 0) {
            $this->stateAssembled = true;
            $this->add(new Link(
                $this->item->services_unknown_unhandled,
                $this->url->addFilter(Filter::matchAll(
                    Filter::where('state.soft_state', 3),
                    Filter::where('state.is_handled', 'n')
                )),
                [
                    'class' => 'state-unknown',
                    'title' => sprintf(tp(
                        'List %d service that is currently in UNKNOWN state in service group "%s"',
                        'List %d services which are currently in UNKNOWN state in service group "%s"',
                        $this->item->services_unknown_unhandled
                    ), $this->item->services_unknown_unhandled, $this->item->display_name)
                ]
            ));
        } elseif ($this->item->services_unknown_handled > 0) {
            $this->stateAssembled = true;
            $this->add(new Link(
                $this->item->services_unknown_handled,
                $this->url->addFilter(Filter::matchAll(
                    Filter::where('state.soft_state', 3),
                    Filter::where('state.is_handled', 'y')
                )),
                [
                    'class' => 'state-unknown handled',
                    'title' => sprintf(tp(
                        'List %d service that is currently in UNKNOWN (Acknowledged) state in service group "%s"',
                        'List %d services which are currently in UNKNOWN (Acknowledged) state in service group "%s"',
                        $this->item->services_unknown_handled
                    ), $this->item->services_unknown_handled, $this->item->display_name)
                ]
            ));
        } elseif ($this->item->services_pending > 0) {
            $this->stateAssembled = true;
            $this->add(new Link(
                $this->item->services_pending,
                $this->url->addFilter(Filter::matchAll(
                    Filter::where('state.soft_state', 99)
                )),
                [
                    'class' => 'state-pending',
                    'title' => sprintf(tp(
                        'List %d service that is currently in PENDING state in service group "%s"',
                        'List %d services which are currently in PENDING state in service group "%s"',
                        $this->item->services_pending
                    ), $this->item->services_pending, $this->item->display_name)
                ]
            ));
        } elseif ($this->item->services_ok > 0) {
            $this->stateAssembled = true;
            $this->add(new Link(
                $this->item->services_ok,
                $this->url->addFilter(Filter::matchAll(
                    Filter::where('state.soft_state', 0)
                )),
                [
                    'class' => 'state-ok',
                    'title' => sprintf(tp(
                        'List %d service that is currently in OK state in service group "%s"',
                        'List %d services which are currently in OK state in service group "%s"',
                        $this->item->services_ok
                    ), $this->item->services_ok, $this->item->display_name)
                ]
            ));
        }
    }
}
