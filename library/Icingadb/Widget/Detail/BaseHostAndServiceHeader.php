<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Model\State;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Orm\Model;

class BaseHostAndServiceHeader extends BaseObjectHeader
{
    use HostAndServiceHeaderUtils;

    /** @var State */
    protected $state;

    protected function init(): void
    {
        $this->setObject($this->object);
        $this->state = $this->object->state;
    }

    protected function createSubject(): ValidHtml
    {
        return new HtmlElement(
            'span',
            Attributes::create(['class' => 'subject']),
            Text::create($this->object->display_name)
        );
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml($this->createTitle());
        $header->addHtml($this->createCaption());
        $header->add($this->createTimestamp());
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());
    }

    protected function assemble(): void
    {
        if ($this->state->is_overdue) {
            $this->addAttributes(['class' => 'overdue']);
        }

        $this->add([
            $this->createVisual(),
            $this->createIconImage(),
            $this->createMain()
        ]);
    }
}
