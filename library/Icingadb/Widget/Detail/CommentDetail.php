<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\MarkdownText;
use Icinga\Module\Icingadb\Forms\Command\Object\DeleteCommentForm;
use Icinga\Module\Icingadb\Widget\TimeUntil;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class CommentDetail extends BaseHtmlElement
{
    use Auth;

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
            Html::tag('h2', t('Comment')),
            new MarkdownText($this->comment->text)
        ];
    }

    protected function createDetails()
    {
        $details = [];

        if ($this->comment->expire_time != 0) {
            $details[] = Html::tag(
                'p',
                Html::sprintf(
                    t('This acknowledgement expires %s.', '..<time-until>'),
                    new TimeUntil($this->comment->expire_time)
                )
            );
        }

        if ($this->comment->is_sticky) {
            $details[] = Html::tag('p', t('This acknowledgement is sticky.'));
        }

        if (! empty($details)) {
            array_unshift($details, Html::tag('h2', t('Details')));
        }

        return $details;
    }

    protected function createRemoveCommentForm()
    {
        $action = Links::commentsDelete();
        $action->setParam('name', $this->comment->name);

        return (new DeleteCommentForm())
            ->setObjects([$this->comment])
            ->populate(['redirect' => '__BACK__'])
            ->setAction($action->getAbsoluteUrl());
    }

    protected function assemble()
    {
        $this->add($this->createComment());

        $details = $this->createDetails();

        if (! empty($details)) {
            $this->add($details);
        }

        if (
            $this->isGrantedOn(
                'icingadb/command/comment/delete',
                $this->comment->{$this->comment->object_type}
            )
        ) {
            $this->add($this->createRemoveCommentForm());
        }
    }
}
