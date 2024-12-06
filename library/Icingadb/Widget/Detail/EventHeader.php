<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Model\Comment;
use Icinga\Module\Icingadb\Model\History;
use ipl\Html\BaseHtmlElement;
use ipl\Web\Widget\StateBall;

/**
 * @property History $object
 */
class EventHeader extends BaseObjectHeader
{
    use EventHeaderUtils;

    protected $defaultAttributes = ['class' => 'event-header'];

    protected function getObject(): History
    {
        return $this->object;
    }

    protected function getStateBallSize(): string
    {
        return StateBall::SIZE_BIG;
    }

    protected function wantSubjectLink(): bool
    {
        return false;
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml($this->createTitle());
        $header->addHtml($this->createTimestamp());
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());
    }

    protected function assemble(): void
    {
        $this->addHtml($this->createVisual());
        $this->addHtml($this->createMain());
    }
}
