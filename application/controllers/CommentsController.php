<?php

namespace Icinga\Module\Eagle\Controllers;

use Icinga\Module\Eagle\Model\Comment;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\ItemList\CommentList;

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
        $filterControl = $this->createFilterControl($comments);

        $this->filter($comments);

        yield $this->export($comments);

        $this->addControl($paginationControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $this->addContent(new CommentList($comments));
    }
}
