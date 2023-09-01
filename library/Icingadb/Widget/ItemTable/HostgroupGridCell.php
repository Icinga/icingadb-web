<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use ipl\Stdlib\Filter;
use ipl\Web\Url;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBadge;

class HostgroupGridCell extends BaseHostGroupItem
{
    use GridCellLayout;

    protected $defaultAttributes = ['class' => ['group-grid-cell', 'hostgroup-grid-cell']];

    protected function createGroupBadge(): Link
    {
        $url = Url::fromPath('icingadb/hosts');
        $urlFilter = Filter::all(Filter::equal('hostgroup.name', $this->item->name));

        if ($this->item->hosts_down_unhandled > 0) {
            $urlFilter->add(Filter::equal('host.state.soft_state', 1))
                ->add(Filter::equal('host.state.is_handled', 'n'))
                ->add(Filter::equal('host.state.is_reachable', 'y'));

            return new Link(
                new StateBadge($this->item->hosts_down_unhandled, 'down'),
                $url->setFilter($urlFilter),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d host that is currently in DOWN state in host group "%s"',
                            'List %d hosts which are currently in DOWN state in host group "%s"',
                            $this->item->hosts_down_unhandled
                        ),
                        $this->item->hosts_down_unhandled,
                        $this->item->display_name
                    )
                ]
            );
        } elseif ($this->item->hosts_down_handled > 0) {
            $urlFilter->add(Filter::equal('host.state.soft_state', 1))
                ->add(Filter::any(
                    Filter::equal('host.state.is_handled', 'y'),
                    Filter::equal('host.state.is_reachable', 'n')
                ));

            return new Link(
                new StateBadge($this->item->hosts_down_handled, 'down', true),
                $url->setFilter($urlFilter),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d host that is currently in DOWN (Acknowledged) state in host group "%s"',
                            'List %d hosts which are currently in DOWN (Acknowledged) state in host group "%s"',
                            $this->item->hosts_down_handled
                        ),
                        $this->item->hosts_down_handled,
                        $this->item->display_name
                    )
                ]
            );
        } elseif ($this->item->hosts_pending > 0) {
            $urlFilter->add(Filter::equal('host.state.soft_state', 99));

            return new Link(
                new StateBadge($this->item->hosts_pending, 'pending'),
                $url->setFilter($urlFilter),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d host that is currently in PENDING state in host group "%s"',
                            'List %d hosts which are currently in PENDING state in host group "%s"',
                            $this->item->hosts_pending
                        ),
                        $this->item->hosts_pending,
                        $this->item->display_name
                    )
                ]
            );
        } elseif ($this->item->hosts_up > 0) {
            $urlFilter->add(Filter::equal('host.state.soft_state', 0));

            return new Link(
                new StateBadge($this->item->hosts_up, 'up'),
                $url->setFilter($urlFilter),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d host that is currently in UP state in host group "%s"',
                            'List %d hosts which are currently in UP state in host group "%s"',
                            $this->item->hosts_up
                        ),
                        $this->item->hosts_up,
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
                    $this->translate('There are no hosts in host group "%s"'),
                    $this->item->display_name
                )
            ]
        );
    }
}
