<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Model\RedundancyGroupSummary;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\StateBall;

/**
 * @property RedundancyGroup $object
 */
class RedundancyGroupHeader extends BaseObjectHeader
{
    use RedundancyGroupHeaderUtils;

    protected $defaultAttributes = ['class' => 'redundancygroup-header'];

    /** @var RedundancyGroupSummary */
    protected $summary;

    public function __construct(RedundancyGroup $object, RedundancyGroupSummary $summary)
    {
        $this->summary = $summary;

        parent::__construct($object);
    }

    protected function getObject(): RedundancyGroup
    {
        return $this->object;
    }

    protected function getSummary(): RedundancyGroupSummary
    {
        return $this->summary;
    }

    protected function createSubject(): ValidHtml
    {
        return new HtmlElement(
            'span',
            Attributes::create(['class' => 'subject']),
            Text::create($this->object->display_name)
        );
    }

    protected function getStateBallSize(): string
    {
        return StateBall::SIZE_BIG;
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml($this->createTitle());
        $header->addHtml($this->createCaption());
        $header->addHtml($this->createTimestamp());
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());
    }
}
