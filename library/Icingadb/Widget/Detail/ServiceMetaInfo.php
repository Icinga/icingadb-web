<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Date\DateFormatter;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Web\Widget\VerticalKeyValue;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Icon;

class ServiceMetaInfo extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'object-meta-info'];

    /** @var Service */
    protected $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    protected function assemble()
    {
        $this->addHtml(
            new VerticalKeyValue('service.name', $this->service->name),
            new VerticalKeyValue(
                'last_state_change',
                DateFormatter::formatDateTime($this->service->state->last_state_change)
            )
        );

        $collapsible = new HtmlElement('div', Attributes::create([
            'class'                 => 'collapsible',
            'id'                    => 'object-meta-info',
            'data-toggle-element'   => '.object-meta-info-control',
            'data-visible-height'   => 0
        ]));

        $renderHelper = new HtmlDocument();
        $renderHelper->addHtml(
            $this,
            new HtmlElement(
                'button',
                Attributes::create(['class' => 'object-meta-info-control']),
                new Icon('angle-double-up', ['class' => 'collapse-icon']),
                new Icon('angle-double-down', ['class' => 'expand-icon'])
            )
        );

        $this->addWrapper($collapsible);
        $this->addWrapper($renderHelper);
    }
}
