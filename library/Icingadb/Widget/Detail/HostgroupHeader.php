<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Model\Hostgroupsummary;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;

/**
 * @property Hostgroupsummary $object
 */
class HostgroupHeader extends BaseObjectHeader
{
    protected $defaultAttributes = ['class' => 'hostgroup-header'];

    protected function assembleTitle(BaseHtmlElement $title): void
    {
        $title->addHtml(new HtmlElement(
            'span',
            Attributes::create(['class' => 'subject']),
            Text::create($this->object->display_name)
        ));

        $title->addHtml(new HtmlElement('span', null, Text::create($this->object->name)));
    }

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $hostStats = (new HostStatistics($this->object))
            ->setBaseFilter(Filter::equal('hostgroup.name', $this->object->name));


        $serviceStats = (new ServiceStatistics($this->object))
            ->setBaseFilter(Filter::equal('hostgroup.name', $this->object->name));

        $caption->addHtml($hostStats, $serviceStats);
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml($this->createTitle());
        $header->addHtml($this->createCaption());
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());
    }

    protected function assemble(): void
    {
        $this->addHtml($this->createMain());
    }
}
