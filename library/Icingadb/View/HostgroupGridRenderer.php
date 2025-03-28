<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\View;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Hostgroupsummary;
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

/** @implements ItemRenderer<Hostgroupsummary> */
class HostgroupGridRenderer implements ItemRenderer
{
    use Translation;
    use BaseFilter;

    public function assembleAttributes($item, Attributes $attributes, string $layout): void
    {
        $attributes->get('class')->addValue(['object-grid-cell', 'hostgroup']);
    }

    public function assembleVisual($item, HtmlDocument $visual, string $layout): void
    {
        $url = Url::fromPath('icingadb/hosts');
        $urlFilter = Filter::all(Filter::equal('hostgroup.name', $item->name));

        if ($item->hosts_down_unhandled > 0) {
            $urlFilter->add(Filter::equal('host.state.soft_state', 1))
                ->add(Filter::equal('host.state.is_handled', 'n'))
                ->add(Filter::equal('host.state.is_reachable', 'y'));

            $link = new Link(
                new StateBadge($item->hosts_down_unhandled, 'down'),
                $url->setFilter($urlFilter),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d host that is currently in DOWN state in host group "%s"',
                            'List %d hosts which are currently in DOWN state in host group "%s"',
                            $item->hosts_down_unhandled
                        ),
                        $item->hosts_down_unhandled,
                        $item->display_name
                    )
                ]
            );
        } elseif ($item->hosts_down_handled > 0) {
            $urlFilter->add(Filter::equal('host.state.soft_state', 1))
                ->add(Filter::any(
                    Filter::equal('host.state.is_handled', 'y'),
                    Filter::equal('host.state.is_reachable', 'n')
                ));

            $link = new Link(
                new StateBadge($item->hosts_down_handled, 'down', true),
                $url->setFilter($urlFilter),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d host that is currently in DOWN (Acknowledged) state in host group "%s"',
                            'List %d hosts which are currently in DOWN (Acknowledged) state in host group "%s"',
                            $item->hosts_down_handled
                        ),
                        $item->hosts_down_handled,
                        $item->display_name
                    )
                ]
            );
        } elseif ($item->hosts_pending > 0) {
            $urlFilter->add(Filter::equal('host.state.soft_state', 99));

            $link = new Link(
                new StateBadge($item->hosts_pending, 'pending'),
                $url->setFilter($urlFilter),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d host that is currently in PENDING state in host group "%s"',
                            'List %d hosts which are currently in PENDING state in host group "%s"',
                            $item->hosts_pending
                        ),
                        $item->hosts_pending,
                        $item->display_name
                    )
                ]
            );
        } elseif ($item->hosts_up > 0) {
            $urlFilter->add(Filter::equal('host.state.soft_state', 0));

            $link = new Link(
                new StateBadge($item->hosts_up, 'up'),
                $url->setFilter($urlFilter),
                [
                    'title' => sprintf(
                        $this->translatePlural(
                            'List %d host that is currently in UP state in host group "%s"',
                            'List %d hosts which are currently in UP state in host group "%s"',
                            $item->hosts_up
                        ),
                        $item->hosts_up,
                        $item->display_name
                    )
                ]
            );
        } else {
            $link = new Link(
                new StateBadge(0, 'none'),
                Links::hostgroup($item),
                [
                    'title' => sprintf(
                        $this->translate('There are no hosts in host group "%s"'),
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
            Links::hostgroup($item),
            [
                'class' => 'subject',
                'title' => sprintf(
                    $this->translate('List all hosts in the group "%s"'),
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
        $caption->addHtml(Text::create($item->name));
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
