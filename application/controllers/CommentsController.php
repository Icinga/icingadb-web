<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Comment;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ContinueWith;
use Icinga\Module\Icingadb\Widget\ItemList\CommentList;
use Icinga\Module\Icingadb\Widget\ShowMore;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteCommentsCommandForm;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlString;
use ipl\Web\Widget\ActionLink;
use ipl\Web\Widget\Icon;

class CommentsController extends Controller
{
    public function indexAction()
    {
        $this->setTitle($this->translate('Comments'));

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
                'comment.entry_time desc'                 => $this->translate('Entry Time'),
                'host.display_name, service.display_name' => $this->translate('Host'),
                'service.display_name, host.display_name' => $this->translate('Service'),
                'comment.author'                          => $this->translate('Author'),
                'comment.expire_time desc'                => $this->translate('Expire Time')
            ]
        );
        $viewModeSwitcher = $this->createViewModeSwitcher();
        $filterControl = $this->createFilterControl($comments);

        $this->filter($comments);

        yield $this->export($comments);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);
        $this->addControl($filterControl);
        $this->addControl(new ContinueWith($this->getFilter(), Links::commentsDetails()));

        $this->addContent((new CommentList($comments))->setViewMode($viewModeSwitcher->getViewMode()));

        $this->setAutorefreshInterval(10);
    }

    public function deleteAction()
    {
        $this->setTitle($this->translate('Remove Comments'));

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
                $this->translate('Confirm removal of %d comments.'),
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
                    ->add([new Icon('trash'), $this->translate('Remove Comments')])
                    ->setSeparator(' ')
                    ->render(),
                'title'      => $this->translate('Remove comments'),
                'type'       => 'submit'
            ]
        );

        $deleteCommentsForm->handleRequest();

        $this->addContent(HtmlString::create($deleteCommentsForm->render()));
    }

    public function detailsAction()
    {
        $this->setTitle($this->translate('Comments'));

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
            sprintf($this->translate('Show all %d comments'), $comments->count())
        ));

        $this->addContent(new ActionLink(
            sprintf($this->translate('Remove %d comments'), $comments->count()),
            Links::commentsDelete()->setQueryString($this->getFilter()->toQueryString()),
            'trash',
            [
                'class'               => 'cancel-button',
                'data-icinga-modal'   => true,
                'data-no-icinga-ajax' => true
            ]
        ));
    }
}
