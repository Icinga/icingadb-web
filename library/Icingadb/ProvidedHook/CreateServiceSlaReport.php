<?php

namespace Icinga\Module\Icingadb\ProvidedHook;

use Icinga\Module\Icingadb\Hook\ServicesDetailExtensionHook;
use ipl\Html\Html;
use ipl\Html\ValidHtml;
use ipl\Orm\Query;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;

class CreateServiceSlaReport extends ServicesDetailExtensionHook
{
    public function getHtmlForObjects(Query $services): ValidHtml
    {
        $url = Url::fromPath('reporting/reports/new');
        $filter = QueryString::render($this->getBaseFilter());
        $url->addParams(['filter' => $filter, 'report' => 'service']);
        $wrapper = Html::tag('div');
        $wrapper
            ->add(Html::tag('h2', 'Reporting'))
            ->add(Html::tag('a', ['href' => $url], 'Create Service SLA Report'));

        return $wrapper;
    }
}
