<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Common;

use Icinga\Module\Icingadb\Widget\ItemList\ObjectList;
use ipl\Html\HtmlElement;
use ipl\Orm\Query;

/**
 * Paginated object lists embedded in detail views
 */
trait EmbeddedList
{
    /**
     * Create a list with standard pagination, sorting, and search controls
     *
     * The query must already contain the parent scope and access restrictions.
     * User filters are added to this scope. Callers must send a multipart update
     * and skip assembling the detail view when this method returns null.
     *
     * @template TRow of (
     *   \Icinga\Module\Icingadb\Model\DependencyNode
     *   | \Icinga\Module\Icingadb\Model\Service
     *   | \Icinga\Module\Icingadb\Model\Host
     *   | \Icinga\Module\Icingadb\Model\Usergroup
     *   | \Icinga\Module\Icingadb\Model\User
     *   | \Icinga\Module\Icingadb\Model\Comment
     *   | \Icinga\Module\Icingadb\Model\Downtime
     *   | \Icinga\Module\Icingadb\Model\AlertHistory
     * )
     *
     * @param Query<TRow> $query The scoped list query
     * @param array<string, string> $sortColumns Sort specifications and labels
     * @param string[] $preserveParams Parent parameters to preserve in search URLs
     * @param ?string $searchEditorPath Override the filter editor route
     *
     * @return ?HtmlElement The complete list HTML, including its empty state,
     *   or null when a multipart update is prepared
     */
    protected function createEmbeddedList(
        Query $query,
        array $sortColumns,
        array $preserveParams,
        ?string $searchEditorPath = null
    ): ?HtmlElement {
        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($query);
        $sortControl = $this->createSortControl($query, $sortColumns);
        $searchBar = $this->createSearchBar($query, array_merge($preserveParams, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
        ]));
        if ($searchEditorPath !== null) {
            $searchBar->getEditorUrl()->setPath($searchEditorPath);
        }

        if ($searchBar->hasBeenSent() && ! $searchBar->isValid()) {
            if ($searchBar->hasBeenSubmitted()) {
                $filter = $this->getFilter();
            } else {
                $this->addControl($searchBar);

                return null;
            }
        } else {
            $filter = $searchBar->getFilter();
        }

        $query->filter($filter);

        $list = (new ObjectList($query))
            ->setEmptyStateMessage($paginationControl->getEmptyStateMessage());
        $results = HtmlElement::create('div', [
            'id' => $this->getRequest()->protectId('detail-list'),
        ], $list);

        // Keep multipart content nonempty when the paginator has fewer than two pages.
        $pagination = HtmlElement::create('div', [
            'id'    => $this->getRequest()->protectId('detail-list-pagination'),
            'class' => 'pagination-control',
        ], $paginationControl);
        $paginationControl->getAttributes()->remove('class');

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            // Keep the embedded search bar in place while updating its results and pagination.
            /** @var \ipl\Web\Url $redirectUrl Created by the search controls */
            $redirectUrl = $searchBar->getRedirectUrl();
            $paginationControl->setUrl(clone $redirectUrl);
            $this->addControl($searchBar);
            $this->addPart($pagination);
            $this->addPart($results);

            return null;
        }

        return HtmlElement::create('div', null, [
            HtmlElement::create('div', ['class' => 'controls default-layout'], [
                $pagination,
                $sortControl,
                $limitControl,
                $searchBar,
            ]),
            $results,
        ]);
    }
}
