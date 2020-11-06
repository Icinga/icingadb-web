<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Comment;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ContinueWith;
use Icinga\Module\Icingadb\Widget\ItemList\CommentList;
use Icinga\Module\Icingadb\Widget\ShowMore;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteCommentsCommandForm;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlString;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;
use ipl\Web\Widget\Icon;

class CommentsController extends Controller
{
    public function indexAction()
    {
        $this->setTitle(t('Comments'));
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

        $this->handleSearchRequest($comments);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($comments);
        $sortControl = $this->createSortControl(
            $comments,
            [
                'comment.entry_time desc'                 => t('Entry Time'),
                'host.display_name, service.display_name' => t('Host'),
                'service.display_name, host.display_name' => t('Service'),
                'comment.author'                          => t('Author'),
                'comment.expire_time desc'                => t('Expire Time')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher();
        $searchBar = $this->createSearchBar($comments, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam(),
            $viewModeSwitcher->getViewModeParam()
        ]);

        if ($searchBar->hasBeenSent() && ! $searchBar->isValid()) {
            if ($searchBar->hasBeenSubmitted()) {
                $filter = QueryString::parse($this->getFilter()->toQueryString());
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
        $this->addControl(new ContinueWith($this->getFilter(), Links::commentsDetails()));

        $results = $comments->execute();

        $this->addContent((new CommentList($results))->setViewMode($viewModeSwitcher->getViewMode()));

        if ($compact) {
            $this->addContent(
                (new ShowMore($results, Url::fromRequest()->without(['showCompact', 'limit'])))
                    ->setAttribute('data-base-target', '_next')
                    ->setAttribute('title', sprintf(
                        t('Show all %d comments'),
                        $comments->count()
                    ))
            );
        }

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $viewModeSwitcher->setUrl($searchBar->getRedirectUrl());
            $this->sendMultipartUpdate($viewModeSwitcher);
        }

        $this->setAutorefreshInterval(10);
    }

    public function deleteAction()
    {
        $this->setTitle(t('Remove Comments'));

        $db = $this->getDb();

        $comments = Comment::on($db)->with([
            'host',
            'host.state',
            'service',
            'service.host',
            'service.host.state',
            'service.state'
        ]);

        $this->filter($comments);

        $deleteCommentsForm = (new DeleteCommentsCommandForm())
            ->addDescription(sprintf(
                t('Confirm removal of %d comments.'),
                $comments->count()
            ))
            ->setComments($comments)
            ->setRedirectUrl(Links::comments())
            ->create();

        $deleteCommentsForm->removeElement('btn_submit');

        $deleteCommentsForm->addElement(
            'button',
            'btn_submit',
            [
                'class'      => 'cancel-button spinner',
                'decorators' => [
                    'ViewHelper',
                    ['HtmlTag', ['tag' => 'div', 'class' => 'control-group form-controls']]
                ],
                'escape'     => false,
                'ignore'     => true,
                'label'      => (new HtmlDocument())
                    ->add([new Icon('trash'), t('Remove Comments')])
                    ->setSeparator(' ')
                    ->render(),
                'title'      => t('Remove comments'),
                'type'       => 'submit'
            ]
        );

        $deleteCommentsForm->handleRequest();

        $this->addContent(HtmlString::create($deleteCommentsForm->render()));
    }

    public function detailsAction()
    {
        $this->setTitle(t('Comments'));

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

        $this->addControl((new CommentList($rs))->setViewMode('minimal'));

        $this->addControl(new ShowMore(
            $rs,
            Links::comments()->setQueryString($this->getFilter()->toQueryString()),
            sprintf(t('Show all %d comments'), $comments->count())
        ));

        $this->addContent(new ActionLink(
            sprintf(t('Remove %d comments'), $comments->count()),
            Links::commentsDelete()->setQueryString($this->getFilter()->toQueryString()),
            'trash',
            [
                'class'               => 'cancel-button',
                'data-icinga-modal'   => true,
                'data-no-icinga-ajax' => true
            ]
        ));
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(Comment::class);
        $suggestions->forRequest(ServerRequest::fromGlobals());
        $this->getDocument()->add($suggestions);
    }
}
