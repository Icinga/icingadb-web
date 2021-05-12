<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Date\DateFormatter;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Widget\HorizontalKeyValue;
use Icinga\Module\Icingadb\Widget\VerticalKeyValue;
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
        $this->add([
            new VerticalKeyValue('host_name', $this->host->name),
            new HtmlElement('div', null, [
                new HorizontalKeyValue('host_address', $this->host->address ?: '-'),
                new HorizontalKeyValue('host_address_v6', $this->host->address6 ?: '-')
            ]),
            new VerticalKeyValue(
                'last_state_change',
                DateFormatter::formatDateTime($this->host->state->last_state_change)
            )
        ]);

        $collapsible = new HtmlElement('div', [
            'class' => 'collapsible',
            'id'    => 'host-meta-info',
            'data-toggle-element' => '.host-meta-info-control',
            'data-visible-height' => 0
        ]);

        $renderHelper = new HtmlDocument();
        $renderHelper->add([
            $this,
            new HtmlElement('button', ['class' => 'host-meta-info-control'], [
                new Icon('angle-double-up', ['class' => 'collapse-icon']),
                new Icon('angle-double-down', ['class' => 'expand-icon'])
            ])
        ]);

        $this->addWrapper($collapsible);
        $this->addWrapper($renderHelper);
    }
}
