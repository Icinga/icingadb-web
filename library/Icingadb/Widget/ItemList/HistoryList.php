<?php

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Widget\BaseItemList;
use Icinga\Module\Icingadb\Widget\ItemList\PageSeparatorItem;

class HistoryList extends BaseItemList
{
    protected $defaultAttributes = ['class' => 'history-list'];

    protected $pageSize;

    protected function init()
    {
        $this->realData = $this->data;
        $this->data = $this->getIterator();
    }

    public function setPageSize($size)
    {
        $this->pageSize = $size;

        return $this;
    }

    protected function getItemClass()
    {
        return HistoryListItem::class;
    }

    protected function getIterator()
    {
        $count = 0;
        $pageNumber = 1;

        foreach ($this->realData as $data) {
            $count++;

            if ($count % $this->pageSize === 0) {
                $pageNumber++;
            } elseif ($count > $this->pageSize && $count % $this->pageSize === 1) {
                $this->add(new PageSeparatorItem($pageNumber));
            }

            yield $data;
        }
    }
}
