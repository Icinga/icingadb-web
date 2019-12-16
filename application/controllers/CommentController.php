<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Common\CommandActions;
use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Model\Comment;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\CommentDetail;
use Icinga\Module\Icingadb\Widget\ItemList\CommentList;

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

        $this->applyMonitoringRestriction($query);

        $comment = $query->first();
        if ($comment === null) {
            throw new NotFoundError($this->translate('Comment not found'));
        }

        $this->comment = $comment;
    }

    public function indexAction()
    {
        $this->addControl((new CommentList([$this->comment]))->setViewMode('minimal')->setCaptionDisabled());

        $this->addContent(new CommentDetail($this->comment));

        $this->setAutorefreshInterval(10);
    }

    protected function fetchCommandTargets()
    {
        return [$this->comment];
    }

    protected function getCommandTargetsUrl()
    {
        return Links::comment($this->comment);
    }
}
