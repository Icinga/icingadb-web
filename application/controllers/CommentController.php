<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Model\Comment;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\CommentDetail;
use Icinga\Module\Icingadb\Widget\Detail\ObjectHeader;
use ipl\Stdlib\Filter;

class CommentController extends Controller
{
    /** @var Comment The comment object */
    protected $comment;

    public function init()
    {
        $this->addTitleTab(t('Comment'));

        $name = $this->params->getRequired('name');

        $query = Comment::on($this->getDb())->with([
            'host',
            'host.state',
            'service',
            'service.state',
            'service.host',
            'service.host.state'
        ]);
        $query->filter(Filter::equal('comment.name', $name));

        $this->applyRestrictions($query);

        $comment = $query->first();
        if ($comment === null) {
            throw new NotFoundError(t('Comment not found'));
        }

        $this->comment = $comment;
    }

    public function indexAction()
    {
        $this->addControl(new ObjectHeader($this->comment));

        $this->addContent(new CommentDetail($this->comment));

        $this->setAutorefreshInterval(10);
    }
}
