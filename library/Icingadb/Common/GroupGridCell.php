<?php

namespace Icinga\Module\Icingadb\Common;

use Icinga\Module\Icingadb\Model\Hostgroupsummary;
use Icinga\Module\Icingadb\Model\ServicegroupSummary;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Filter;
use ipl\Web\Url;

abstract class GroupGridCell extends BaseHtmlElement
{
    protected $defaultAttributes = ['class' => 'group-grid-cell',];

    protected $tag = 'div';

    /**
     * A host or service group summary object
     *
     * @var Hostgroupsummary|ServicegroupSummary
     */
    protected $item;

    /**
     * @var BaseItemList
     */
    protected $list;

    /**
     * Url to which the user is redirected when clicking on a group
     *
     * @var Url
     */
    protected $url;

    /**
     * Whether any of the group summary states have already been rendered and
     *
     * @var bool
     */
    protected $stateAssembled = false;

    /**
     * Create a new group grid cell
     *
     * @param $item
     * @param BaseItemList $list
     */
    public function __construct($item, BaseItemList $list)
    {
        $this->item = $item;
        $this->list = $list;

        $this->init();
    }

    /**
     * Initialize this group grid cell
     *
     * When you override this method don't forget to call parent::init().
     */
    protected function init()
    {
        $this->list->addDetailFilterAttribute($this, Filter::equal('name', $this->item->name));
    }

    abstract protected function assembleContent();

    /**
     * Will only be rendered when the given group is empty
     */
    protected function assembleNone()
    {
        if (! $this->stateAssembled) {
            $this->add(HtmlElement::create('div', ['class' => 'state-none'], 0));
        }
    }

    protected function assembleLabel()
    {
    }

    protected function assemble()
    {
        $this->assembleContent();
        $this->assembleNone();

        $this->assembleLabel();
    }
}
