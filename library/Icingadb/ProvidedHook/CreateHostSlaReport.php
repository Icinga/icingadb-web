<?php

namespace Icinga\Module\Icingadb\ProvidedHook;

use Icinga\Module\Icingadb\Hook\HostsDetailExtensionHook;
use ipl\Html\Html;
use ipl\Html\ValidHtml;
use ipl\Orm\Query;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;

class CreateHostSlaReport extends HostsDetailExtensionHook
{
    public function getHtmlForObjects(Query $hosts): ValidHtml
    {
        $url = Url::fromPath('reporting/reports/new');
        $filter = QueryString::render($this->getBaseFilter());
        $url->addParams(['filter' => $filter, 'report' => 'host']);
        $wrapper = Html::tag('div');
        $wrapper
            ->add(Html::tag('h2', 'Reporting'))
            ->add(Html::tag('a', ['href' => $url], 'Create Host SLA Report'));

        return $wrapper;
    }
}
