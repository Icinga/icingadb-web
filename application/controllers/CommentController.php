<?php

namespace Icinga\Module\Eagle\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Eagle\Common\CommandActions;
use Icinga\Module\Eagle\Common\HostLink;
use Icinga\Module\Eagle\Common\Links;
use Icinga\Module\Eagle\Common\ServiceLink;
use Icinga\Module\Eagle\Model\Comment;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\Detail\CommentDetail;

class CommentController extends Controller
{
    use CommandActions;
    use HostLink;
    use ServiceLink;

    /** @var Comment The comment object */
    protected $comment;

    public function init()
    {
        $this->setTitle($this->translate('Comment'));

        $name = $this->params->shiftRequired('name');

        $query = Comment::on($this->getDb())
            ->with('host')
            ->with('host.state');

        $query->getSelectBase()
            ->where(['comment.name = ?' => $name]);

        $comment = $query->first();
        if ($comment === null) {
            throw new NotFoundError($this->translate('Comment not found'));
        }

        $this->comment = $comment;
    }

    protected function fetchCommandTargets()
    {
        return [$this->comment];
    }

    protected function getCommandTargetsUrl()
    {
        return Links::comment($this->comment);
    }

    public function indexAction()
    {
        $detail = new CommentDetail($this->comment);

        $this->addControl($detail->getControl());
        $this->addContent($detail);
    }
}
