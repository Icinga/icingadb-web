<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Model\State;
use Icinga\Module\Icingadb\Widget\Detail\DetailAssembler;
use ipl\Html\HtmlElement;
use ipl\I18n\Translation;
use ipl\Orm\Model;
use ipl\Web\Common\BaseListItem;
use ipl\Html\BaseHtmlElement;

/**
 * Host or service item of a host or service list. Represents one database row.
 */
abstract class StateListItem extends BaseListItem
{
    use Translation;
    use DetailAssembler;

    /** @var StateList The list where the item is part of */
    protected $list;

    /** @var State The state of the item */
    protected $state;

    protected function init(): void
    {
        $this->state = $this->item->state;

        if (isset($this->item->icon_image->icon_image)) {
            $this->list->setHasIconImages(true);
        }
    }

    abstract protected function getStateBallSize(): string;

    protected function getObject(): Model
    {
        return $this->item;
    }

    /**
     * @return ?BaseHtmlElement
     */
    protected function createIconImage(): ?BaseHtmlElement
    {
        if (! $this->list->hasIconImages()) {
            return null;
        }

        $iconImage = HtmlElement::create('div', [
            'class' => 'icon-image',
        ]);

        $this->assembleIconImage($iconImage);

        return $iconImage;
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
