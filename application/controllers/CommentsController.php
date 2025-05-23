<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Forms\Command\Object\DeleteCommentForm;
use Icinga\Module\Icingadb\Model\Comment;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Web\Control\ViewModeSwitcher;
use Icinga\Module\Icingadb\Widget\ItemList\ObjectList;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Url;

class CommentsController extends Controller
{
    use CommandActions;

    public function indexAction()
    {
        $this->addTitleTab(t('Comments'));
        $compact = $this->view->compact;

        $db = $this->getDb();

        $comments = Comment::on($db)->with([
            'host',
            'host.state',
            'service',
            'service.host',
            'service.host.state',
            'service.state'
        ]);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($comments);
        $sortControl = $this->createSortControl(
            $comments,
            [
                'comment.entry_time desc'  => t('Entry Time'),
                'host.display_name'        => t('Host'),
                'service.display_name'     => t('Service'),
                'comment.author'           => t('Author'),
                'comment.expire_time desc' => t('Expire Time')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher($paginationControl, $limitControl);
        $searchBar = $this->createSearchBar($comments, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
            $viewModeSwitcher->getViewModeParam()
        ]);

        if ($searchBar->hasBeenSent() && ! $searchBar->isValid()) {
            if ($searchBar->hasBeenSubmitted()) {
                $filter = $this->getFilter();
            } else {
                $this->addControl($searchBar);
                $this->sendMultipartUpdate();
                return;
            }
        } else {
            $filter = $searchBar->getFilter();
        }

        $this->filter($comments, $filter);

        $comments->peekAhead($compact);

        yield $this->export($comments);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($searchBar);
        $continueWith = $this->createContinueWith(Links::commentsDetails(), $searchBar);

        $results = $comments->execute();

        $this->addContent(
            (new ObjectList($results))
                ->setViewMode($viewModeSwitcher->getViewMode())
                ->setEmptyStateMessage($paginationControl->getEmptyStateMessage())
        );

        if ($compact) {
            $this->addContent(
                (new ShowMore($results, Url::fromRequest()->without(['showCompact', 'limit', 'view'])))
                    ->setBaseTarget('_next')
                    ->setAttribute('title', sprintf(
                        t('Show all %d comments'),
                        $comments->count()
                    ))
            );
        }

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate($continueWith);
        }

        $this->setAutorefreshInterval(10);
    }

    public function deleteAction()
    {
        $this->assertIsGrantedOnCommandTargets('icingadb/command/comment/delete');
        $this->setTitle(t('Remove Comments'));
        $this->handleCommandForm(DeleteCommentForm::class);
    }

    public function detailsAction()
    {
        $this->addTitleTab(t('Comments'));

        $db = $this->getDb();

        $comments = Comment::on($db)->with([
            'host',
            'host.state',
            'service',
            'service.host',
            'service.host.state',
            'service.state'
        ]);

        $comments->limit(3)->peekAhead();

        $this->filter($comments);

        yield $this->export($comments);

        $rs = $comments->execute();

        $this->addControl(
            (new ObjectList($rs))
                ->setViewMode('minimal')
                ->setDetailActionsDisabled()
        );

        $this->addControl(new ShowMore(
            $rs,
            Links::comments()->setFilter($this->getFilter()),
            sprintf(t('Show all %d comments'), $comments->count())
        ));

        $this->addContent(
            (new DeleteCommentForm())
                ->setObjects($comments)
                ->setAction(
                    Links::commentsDelete()
                        ->setFilter($this->getFilter())
                        ->getAbsoluteUrl()
                )
        );
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(Comment::class);
        $suggestions->forRequest(ServerRequest::fromGlobals());
        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction()
    {
        $editor = $this->createSearchEditor(Comment::on($this->getDb()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            ViewModeSwitcher::DEFAULT_VIEW_MODE_PARAM
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }

    protected function getCommandTargetsUrl(): Url
    {
        return Url::fromPath('__CLOSE__');
    }

    protected function fetchCommandTargets(): Query
    {
        $comments = Comment::on($this->getDb())->with([
            'host',
            'host.state',
            'service',
            'service.host',
            'service.host.state',
            'service.state'
        ]);

        $this->filter($comments);

        return $comments;
    }

    public function isGrantedOn(string $permission, Model $object): bool
    {
        return parent::isGrantedOn($permission, $object->{$object->object_type});
    }

    public function isGrantedOnType(string $permission, string $type, Filter\Rule $filter, bool $cache = true): bool
    {
        return parent::isGrantedOnType($permission, 'host', $filter, $cache)
            || parent::isGrantedOnType($permission, 'service', $filter, $cache);
    }
}
