<?php

/* Icinga DB Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\View;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Host;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\Link;

/** @extends BaseHostAndServiceRenderer<Host> */
class HostRenderer extends BaseHostAndServiceRenderer
{
    public function assembleAttributes($item, Attributes $attributes, string $layout): void
    {
        parent::assembleAttributes($item, $attributes, $layout);

        $attributes->get('class')->addValue('host');
    }

    protected function createSubject($item, string $layout): ValidHtml
    {
        if ($layout === 'header') {
            return new HtmlElement('span', new Attributes(['class' => 'subject']), new Text($item->display_name));
        }

        return new Link($item->display_name, Links::host($item), ['class' => 'subject']);
    }
}
