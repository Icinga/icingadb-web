<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Date\DateFormatter;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Model\Comment;
use Icinga\Module\Icingadb\Widget\MarkdownText;
use Icinga\Module\Icingadb\Forms\Command\Object\DeleteCommentForm;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\StateBall;
use ipl\Web\Widget\TimeUntil;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class CommentDetail extends BaseHtmlElement
{
    use Auth;

    protected $comment;

    protected $defaultAttributes = ['class' => ['object-detail', 'comment-detail']];

    protected $tag = 'div';

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    protected function createComment(): array
    {
        return [
            Html::tag('h2', t('Comment')),
            new MarkdownText($this->comment->text)
        ];
    }

    protected function createDetails(): array
    {
        $details = [];

        if (getenv('ICINGAWEB_EXPORT_FORMAT') === 'pdf') {
            if ($this->comment->object_type === 'host') {
                $details[] = new HorizontalKeyValue(t('Host'), [
                    $this->comment->host->name,
                    ' ',
                    new StateBall($this->comment->host->state->getStateText())
                ]);
            } else {
                $details[] = new HorizontalKeyValue(t('Service'), Html::sprintf(
                    t('%s on %s', '<service> on <host>'),
                    [$this->comment->service->name, ' ', new StateBall($this->comment->service->state->getStateText())],
                    $this->comment->host->name
                ));
            }

            $details[] = new HorizontalKeyValue(t('Author'), $this->comment->author);
            $details[] = new HorizontalKeyValue(
                t('Acknowledgement'),
                $this->comment->entry_type === 'ack' ? t('Yes') : t('No')
            );
            $details[] = new HorizontalKeyValue(
                t('Persistent'),
                $this->comment->is_persistent ? t('Yes') : t('No')
            );
            $details[] = new HorizontalKeyValue(
                t('Created'),
                DateFormatter::formatDateTime($this->comment->entry_time)
            );
            $details[] = new HorizontalKeyValue(t('Expires'), $this->comment->expire_time != 0
                ? DateFormatter::formatDateTime($this->comment->expire_time)
                : t('Never'));
        } else {
            if ($this->comment->expire_time != 0) {
                $details[] = Html::tag(
                    'p',
                    Html::sprintf(
                        $this->comment->entry_type === 'ack'
                            ? t('This acknowledgement expires %s.', '..<time-until>')
                            : t('This comment expires %s.', '..<time-until>'),
                        new TimeUntil($this->comment->expire_time)
                    )
                );
            }

            if ($this->comment->is_sticky) {
                $details[] = Html::tag('p', t('This acknowledgement is sticky.'));
            }
        }

        if (! empty($details)) {
            array_unshift($details, Html::tag('h2', t('Details')));
        }

        return $details;
    }

    protected function createRemoveCommentForm()
    {
        if (getenv('ICINGAWEB_EXPORT_FORMAT') === 'pdf') {
            return null;
        }

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
