<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\View;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;

/** @extends BaseHostAndServiceRenderer<Service> */
class ServiceRenderer extends BaseHostAndServiceRenderer
{
    public function assembleAttributes($item, Attributes $attributes, string $layout): void
    {
        parent::assembleAttributes($item, $attributes, $layout);

        $attributes->get('class')->addValue('service');
    }

    protected function createSubject($item, string $layout): ValidHtml
    {
        $service = $item->display_name;
        $host = [
            new StateBall($item->host->state->getStateText(), StateBall::SIZE_MEDIUM),
            ' ',
            $item->host->display_name
        ];

        $host = new Link($host, Links::host($item->host), ['class' => 'subject']);
        if ($layout === 'header') {
            $service = new HtmlElement('span', new Attributes(['class' => 'subject']), new Text($service));
        } else {
            $service = new Link($service, Links::service($item, $item->host), ['class' => 'subject']);
        }

        return Html::sprintf($this->translate('%s on %s', '<service> on <host>'), $service, $host);
    }
}
