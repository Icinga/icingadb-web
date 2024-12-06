<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Model\Downtime;
use ipl\Html\BaseHtmlElement;

/**
 * @property Downtime $object
 */
class DowntimeHeader extends BaseObjectHeader
{
    use DowntimeHeaderUtils;

    protected $defaultAttributes = ['class' => 'downtime-header'];

    protected function getObject(): Downtime
    {
        return $this->object;
    }

    protected function wantSubjectLink(): bool
    {
        return false;
    }

    protected function wantObjectLink(): bool
    {
        return true;
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
