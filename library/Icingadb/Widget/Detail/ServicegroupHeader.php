<?php

namespace Icinga\Module\Icingadb\Widget\Detail;

use ipl\Html\BaseHtmlElement;
use ipl\Stdlib\Filter;

class ServicegroupHeader extends HostgroupHeader
{
    protected $defaultAttributes = ['class' => 'servicegroup-header'];

    protected function assembleCaption(BaseHtmlElement $caption): void
    {
        $caption->addHtml(
            (new ServiceStatistics($this->object))
                ->setBaseFilter(Filter::equal('servicegroup.name', $this->object->name))
        );
    }
}
