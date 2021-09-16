<?php

namespace Icinga\Module\Icingadb\Widget\Grid;

use Icinga\Data\Filter\Filter;
use Icinga\Module\Icingadb\Common\GroupGridCell;
use ipl\Web\Url;
use ipl\Web\Widget\Link;

class HostgroupGridCell extends GroupGridCell
{
    protected function init()
    {
        $this->url = Url::fromPath('icingadb/hosts')->addParams(['hostgroup.name' => $this->item->name]);

        parent::init();
    }

    protected function assembleLabel()
    {
        $this->add(new Link($this->item->display_name, Url::fromPath('icingadb/hostgroup')->addParams([
            'name' => $this->item->name
        ]), ['title' => sprintf(t('List all hosts in the group "%s"'), $this->item->display_name)]));
    }

    protected function assembleContent()
    {
        if ($this->item->hosts_down_unhandled > 0) {
            $this->stateAssembled = true;
            $this->add(new Link(
                $this->item->hosts_down_unhandled,
                $this->url->addFilter(Filter::matchAll(
                    Filter::where('state.soft_state', 1),
                    Filter::where('state.is_handled', 'n')
                )),
                [
                    'class' => 'state-down',
                    'title' => sprintf(tp(
                        'List %d host that is currently in DOWN state in host group "%s"',
                        'List %d hosts which are currently in DOWN state in host group "%s"',
                        $this->item->hosts_down_unhandled
                    ), $this->item->hosts_down_unhandled, $this->item->display_name)
                ]
            ));
        } elseif ($this->item->hosts_down_handled > 0) {
            $this->stateAssembled = true;
            $this->add(new Link(
                $this->item->hosts_down_handled,
                $this->url->addFilter(Filter::matchAll(
                    Filter::where('state.soft_state', 1),
                    Filter::where('state.is_handled', 'y')
                )),
                [
                    'class' => 'state-down handled',
                    'title' => sprintf(tp(
                        'List %d host that is currently in DOWN (Acknowledged) state in host group "%s"',
                        'List %d hosts which are currently in DOWN (Acknowledged) state in host group "%s"',
                        $this->item->hosts_down_handled
                    ), $this->item->hosts_down_handled, $this->item->display_name)
                ]
            ));
        } elseif ($this->item->hosts_unreachable_unhandled > 0) {
            $this->stateAssembled = true;
            $this->add(new Link(
                $this->item->hosts_unreachable_unhandled,
                $this->url->addFilter(Filter::matchAll(
                    Filter::where('state.soft_state', 2),
                    Filter::where('state.is_handled', 'n')
                )),
                [
                    'class' => 'state-unreachable',
                    'title' => sprintf(tp(
                        'List %d host that is currently in UNREACHABLE state in host group "%s"',
                        'List %d hosts which are currently in UNREACHABLE state in host group "%s"',
                        $this->item->hosts_unreachable_unhandled
                    ), $this->item->hosts_unreachable_unhandled, $this->item->display_name)
                ]
            ));
        } elseif ($this->item->hosts_unreachable_handled > 0) {
            $this->stateAssembled = true;
            $this->add(new Link(
                $this->item->hosts_unreachable_handled,
                $this->url->addFilter(Filter::matchAll(
                    Filter::where('state.soft_state', 2),
                    Filter::where('state.is_handled', 'y')
                )),
                [
                    'class' => 'state-unreachable handled',
                    'title' => sprintf(tp(
                        'List %d host that is currently in UNREACHABLE (Acknowledged) state in host group "%s"',
                        'List %d hosts which are currently in UNREACHABLE (Acknowledged) state in host group "%s"',
                        $this->item->hosts_unreachable_handled
                    ), $this->item->hosts_unreachable_handled, $this->item->display_name)
                ]
            ));
        } elseif ($this->item->hosts_pending > 0) {
            $this->stateAssembled = true;
            $this->add(new Link(
                $this->item->hosts_pending,
                $this->url->addFilter(Filter::matchAll(
                    Filter::where('state.soft_state', 99)
                )),
                [
                    'class' => 'state-pending',
                    'title' => sprintf(tp(
                        'List %d host that is currently in PENDING state in host group "%s"',
                        'List %d hosts which are currently in PENDING state in host group "%s"',
                        $this->item->hosts_pending
                    ), $this->item->hosts_pending, $this->item->display_name)
                ]
            ));
        } elseif ($this->item->hosts_up > 0) {
            $this->stateAssembled = true;
            $this->add(new Link(
                $this->item->hosts_up,
                $this->url->addFilter(Filter::matchAll(
                    Filter::where('state.soft_state', 0)
                )),
                [
                    'class' => 'state-up',
                    'title' => sprintf(tp(
                        'List %d host that is currently in UP state in host group "%s"',
                        'List %d hosts which are currently in UP state in host group "%s"',
                        $this->item->hosts_up
                    ), $this->item->hosts_up, $this->item->display_name)
                ]
            ));
        }
    }
}
