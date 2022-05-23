<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemTable;

use ipl\Html\BaseHtmlElement;
use ipl\Web\Widget\Icon;

abstract class StateItemTable extends BaseItemTable
{
    protected function getVisualLabel()
    {
        return new Icon('heartbeat', ['title' => t('Severity')]);
    }

    protected function assembleColumnHeader(BaseHtmlElement $header, string $name, $label): void
    {
        parent::assembleColumnHeader($header, $name, $label);

        switch (true) {
            case substr($name, -7) === '.output':
            case substr($name, -12) === '.long_output':
                $header->getAttributes()->add('class', 'has-plugin-output');
                break;
            case substr($name, -22) === '.icon_image.icon_image':
                $header->getAttributes()->add('class', 'has-icon-images');
                break;
            case substr($name, -17) === '.performance_data':
            case substr($name, -28) === '.normalized_performance_data':
                $header->getAttributes()->add('class', 'has-performance-data');
                break;
        }
    }
}
