<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\HostLinks;
use Icinga\Module\Icingadb\Common\MarkdownText;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Common\ServiceLinks;
use Icinga\Module\Icingadb\Widget\TimeUntil;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteCommentCommandForm;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlString;
use ipl\Web\Widget\Icon;

class CommentDetail extends BaseHtmlElement
{
    use Auth;
    use HostLink;
    use ServiceLink;

    protected $comment;

    protected $defaultAttributes = ['class' => 'comment-detail'];

    protected $tag = 'div';

    public function __construct($comment)
    {
        $this->comment = $comment;
    }

    protected function createComment()
    {
        return [
            Html::tag('h2', 'Comment'),
            new MarkdownText($this->comment->text)
        ];
    }

    protected function createDetails()
    {
        $details = [];

        if ($this->comment->expire_time != 0) {
            $details[] = Html::tag(
                'p',
                ['This acknowledgement expires', ' ', new TimeUntil($this->comment->expire_time), '.']
            );
        }

        if ($this->comment->is_sticky) {
            $details[] = Html::tag('p', 'This acknowledgement is sticky.');
        }

        if (! empty($details)) {
            array_unshift($details, Html::tag('h2', 'Details'));
        }

        return $details;
    }

    protected function createRemoveCommentForm()
    {
        $formData = [
            'comment_id'   => $this->comment->name,
            'comment_name' => $this->comment->name,
            'redirect'     => '__BACK__'
        ];


        if ($this->comment->object_type === 'host') {
            $action = HostLinks::removeComment($this->comment->host);
        } else {
            $action = ServiceLinks::removeComment($this->comment->service, $this->comment->service->host);
            $formData['comment_is_service'] = true;
        }

        $removeCommentForm = (new DeleteCommentCommandForm())
            ->create()
            ->populate($formData)
            ->setAction($action);

        $submitButton = $removeCommentForm->getElement('btn_submit');
        $submitButton->content = (new HtmlDocument())
            ->add([new Icon('trash'), 'Remove Comment'])
            ->setSeparator(' ')
            ->render();

        return new HtmlString($removeCommentForm->render());
    }

    protected function assemble()
    {
        $this->add($this->createComment());

        $details = $this->createDetails();

        if (! empty($details)) {
            $this->add($details);
        }

        if ($this->getAuth()->hasPermission('monitoring/command/comment/delete')) {
            $this->add($this->createRemoveCommentForm());
        }
    }
}
