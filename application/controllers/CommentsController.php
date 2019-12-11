<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\Comment;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\CommentList;

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
        $filterControl = $this->createFilterControl($comments);

        $this->filter($comments);

        yield $this->export($comments);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $this->addContent(new CommentList($comments));

        $this->setAutorefreshInterval(10);
    }
}
