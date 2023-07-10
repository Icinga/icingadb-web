<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use ipl\Web\Url;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBadge;

class HostgroupGridCell extends BaseHostGroupItem
{
    use GridCellLayout;

    protected $defaultAttributes = ['class' => ['group-grid-cell', 'hostgroup-grid-cell']];

    protected function createGroupBadge(): Link
    {
        $url = Url::fromPath('icingadb/hosts')->addParams(['hostgroup.name' => $this->item->name]);

        if ($this->item->hosts_down_unhandled > 0) {
            return new Link(
                new StateBadge($this->item->hosts_down_unhandled, 'down'),
                $url->addParams([
                    'state.soft_state' => 1,
                    'state.is_handled' => 'n'
                ]),
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
            return new Link(
                new StateBadge($this->item->hosts_down_handled, 'down', true),
                $url->addParams([
                    'state.soft_state' => 1,
                    'state.is_handled' => 'y'
                ]),
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
            return new Link(
                new StateBadge($this->item->hosts_pending, 'pending'),
                $url->addParams(['state.soft_state' => 99]),
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
            return new Link(
                new StateBadge($this->item->hosts_up, 'up'),
                $url->addParams(['state.soft_state' => 0]),
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
