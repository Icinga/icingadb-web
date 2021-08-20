<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Date\DateFormatter;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Widget\HorizontalKeyValue;
use ipl\Web\Widget\VerticalKeyValue;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Icon;

class HostMetaInfo extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'host-meta-info'];

    /** @var Host */
    protected $host;

    public function __construct(Host $host)
    {
        $this->host = $host;
    }

    protected function assemble()
    {
        $this->addHtml(
            new VerticalKeyValue('host.name', $this->host->name),
            new HtmlElement(
                'div',
                null,
                new HorizontalKeyValue('host.address', $this->host->address ?: '-'),
                new HorizontalKeyValue('host.address6', $this->host->address6 ?: '-')
            ),
            new VerticalKeyValue(
                'last_state_change',
                DateFormatter::formatDateTime($this->host->state->last_state_change)
            )
        );

        $collapsible = new HtmlElement('div', Attributes::create([
            'class' => 'collapsible',
            'id'    => 'host-meta-info',
            'data-toggle-element' => '.host-meta-info-control',
            'data-visible-height' => 0
        ]));

        $renderHelper = new HtmlDocument();
        $renderHelper->addHtml(
            $this,
            new HtmlElement(
                'button',
                Attributes::create(['class' => 'host-meta-info-control']),
                new Icon('angle-double-up', ['class' => 'collapse-icon']),
                new Icon('angle-double-down', ['class' => 'expand-icon'])
            )
        );

        $this->addWrapper($collapsible);
        $this->addWrapper($renderHelper);
    }
}
