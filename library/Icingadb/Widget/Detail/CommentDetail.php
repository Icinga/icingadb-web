<?php

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\HostLinks;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Common\ServiceLinks;
use Icinga\Module\Icingadb\Widget\TimeAgo;
use Icinga\Module\Icingadb\Widget\TimeUntil;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteCommentCommandForm;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlString;
use ipl\Web\Widget\Icon;

class CommentDetail extends BaseHtmlElement
{
    use HostLink;
    use ServiceLink;

    protected $comment;

    protected $defaultAttributes = ['class' => 'comment-detail'];

    protected $tag = 'div';

    public function __construct($comment)
    {
        $this->comment = $comment;
    }

    public function getControl()
    {
        return Html::tag('ul', ['class' => 'comment-detail item-list'], [
            Html::tag('li', ['class' => 'list-item'], [
                $this->createVisual(),
                $this->createMain()
            ])
        ]);
    }

    protected function createComment()
    {
        return [
            Html::tag('h2', 'Comment'),
            Html::tag('p', $this->comment->text),
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

    protected function createMain()
    {
        $title = Html::tag('div', ['class' => 'title']);
        $header = Html::tag('header', $title);
        $main = Html::tag('div', ['class' => 'main'], $header);

        $isAck = $this->comment->entry_type === 'ack';
        $expires = $this->comment->expire_time;

        $title->add([
            new Icon(Icons::USER),
            $this->comment->author,
            ' ',
            ($isAck ? 'acknowledged' : 'commented'),
            ' '
        ]);

        if ($this->comment->object_type === 'host') {
            $link = $this->createHostLink($this->comment->host, true);
        } else {
            $link = $this->createServiceLink($this->comment->service, $this->comment->service->host, true);
        }

        $title->add([
            $link,
            ' ',
            new TimeAgo($this->comment->entry_time)
        ]);

        if ($isAck) {
            $label = ['ack'];

            if ($this->comment->is_persistent) {
                array_unshift($label, new Icon(Icons::IS_PERSISTENT));
            }

            $title->add(HTML::tag('span', ['class' => 'ack-badge badge'], $label));
        }

        if ($expires != 0) {
            $title->add(HTML::tag('span', ['class' => 'ack-badge badge'], 'EXPIRES'));
        }

        return $main;
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

    protected function createVisual()
    {
        return Html::tag(
            'div',
            ['class' => 'visual'],
            Html::tag('div', ['class' => 'user-ball'], $this->comment->author[0])
        );
    }

    protected function assemble()
    {
        $this->add($this->createComment());

        $details = $this->createDetails();

        if (! empty($details)) {
            $this->add($details);
        }

        $removeCommentForm = (new DeleteCommentCommandForm())
            ->setAction(HostLinks::removeComment($this->comment->host));

        $submitButton = $removeCommentForm->create()->getElement('btn_submit');
        $submitButton->content = (new HtmlDocument())
            ->add([new Icon('trash'), 'Remove Comment'])
            ->setSeparator(' ')
            ->render();

        $this->add($this->createRemoveCommentForm());
    }
}
