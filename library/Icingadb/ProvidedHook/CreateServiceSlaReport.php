<?php

namespace Icinga\Module\Icingadb\ProvidedHook;

use Icinga\Authentication\Auth;
use Icinga\Module\Icingadb\Hook\ServicesDetailExtensionHook;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Orm\Query;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;
use ipl\Web\Widget\Link;

class CreateServiceSlaReport extends ServicesDetailExtensionHook
{
    use Translation;

    public function getHtmlForObjects(Query $services): ValidHtml
    {
        if (Auth::getInstance()->hasPermission('reporting/reports')) {
            $filter = QueryString::render($this->getBaseFilter());
            return (new HtmlDocument())
                ->addHTML(Html::tag('h2', $this->translate('Reporting')))
                ->addHtml(new Link(
                    $this->translate('Create Service SLA Report'),
                    Url::fromPath('reporting/reports/new')->addParams(['filter' => $filter, 'report' => 'service'])
                ));
        }

        return new HtmlDocument();
    }
}
