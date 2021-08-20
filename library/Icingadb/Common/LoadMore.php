<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Generator;
use Icinga\Module\Icingadb\Widget\ItemList\PageSeparatorItem;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Orm\ResultSet;
use ipl\Web\Url;

trait LoadMore
{
    /** @var int */
    protected $pageSize;

    /** @var int */
    protected $pageNumber;

    /** @var Url */
    protected $loadMoreUrl;

    /**
     * Set the page size
     *
     * @param int $size
     *
     * @return $this
     */
    public function setPageSize($size)
    {
        $this->pageSize = $size;

        return $this;
    }

    /**
     * Set the page number
     *
     * @param int $number
     *
     * @return $this
     */
    public function setPageNumber($number)
    {
        $this->pageNumber = $number;

        return $this;
    }

    /**
     * Set the url to fetch more items
     *
     * @param Url $url
     *
     * @return $this
     */
    public function setLoadMoreUrl(Url $url)
    {
        $this->loadMoreUrl = $url;

        return $this;
    }

    /**
     * Iterate over the given data
     *
     * Add the page separator and the "LoadMore" button at the desired position
     *
     * @param ResultSet $result
     *
     * @return Generator
     */
    protected function getIterator(ResultSet $result)
    {
        $count = 0;
        $pageNumber = $this->pageNumber ?: 1;

        if ($this->loadMoreUrl !== null) {
            $showMore = (new ShowMore(
                $result,
                $this->loadMoreUrl->setParam('page', $pageNumber + 1)
                    ->setAnchor('page-' . ($pageNumber + 1))
            ))
                ->setLabel(t('Load More'))
                ->setAttribute('data-no-icinga-ajax', true);

            if ($pageNumber > 1) {
                $this->add(new PageSeparatorItem($pageNumber));
            }
        }

        foreach ($result as $data) {
            $count++;

            if ($count % $this->pageSize === 0) {
                $pageNumber++;
            } elseif ($count > $this->pageSize && $count % $this->pageSize === 1) {
                $this->add(new PageSeparatorItem($pageNumber));
            }

            yield $data;
        }

        if (isset($showMore)) {
            $this->add($showMore->setTag('li')->addAttributes(['class' => 'list-item']));
        }
    }
}
