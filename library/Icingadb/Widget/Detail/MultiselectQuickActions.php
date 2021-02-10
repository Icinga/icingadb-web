<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\BaseFilter;
use Icinga\Module\Icingadb\Forms\Command\Object\CheckNowForm;
use Icinga\Module\Icingadb\Forms\Command\Object\RemoveAcknowledgementForm;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;

class MultiselectQuickActions extends BaseHtmlElement
{
    use BaseFilter;
    use Auth;

    protected $summary;

    protected $type;

    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'quick-actions'];

    public function __construct($type, $summary)
    {
        $this->summary = $summary;
        $this->type = $type;
    }

    protected function assemble()
    {
        $unacknowledged = "{$this->type}s_problems_unacknowledged";
        $acks = "{$this->type}s_acknowledged";
        $activeChecks = "{$this->type}s_active_checks_enabled";

        if (
            $this->summary->$unacknowledged > $this->summary->$acks
            && $this->getAuth()->hasPermission('monitoring/command/acknowledge-problem')
        ) {
            $this->assembleAction(
                'acknowledge',
                t('Acknowledge'),
                'icon-edit',
                t('Acknowledge this problem, suppress all future notifications for it and tag it as being handled')
            );
        }

        if (
            $this->summary->$acks > 0
            && $this->getAuth()->hasPermission('monitoring/command/remove-acknowledgement')
        ) {
            $removeAckForm = (new RemoveAcknowledgementForm())
                ->setAction($this->getLink('removeAcknowledgement'))
                ->setObjects(array_fill(0, $this->summary->$acks, null));

            $this->add(Html::tag('li', $removeAckForm));
        }

        if (
            $this->getAuth()->hasPermission('monitoring/command/schedule-check')
            || (
                $this->summary->$activeChecks > 0
                && $this->getAuth()->hasPermission('monitoring/command/schedule-check/active-only')
            )
        ) {
            $this->add(Html::tag('li', (new CheckNowForm())->setAction($this->getLink('checkNow'))));
        }

        if ($this->getAuth()->hasPermission('monitoring/command/comment/add')) {
            $this->assembleAction(
                'addComment',
                t('Comment'),
                'icon-comment-empty',
                t('Add a new comment')
            );
        }

        if ($this->getAuth()->hasPermission('monitoring/command/send-custom-notification')) {
            $this->assembleAction(
                'sendCustomNotification',
                t('Notification'),
                'icon-bell',
                t('Send a custom notification')
            );
        }

        if ($this->getAuth()->hasPermission('monitoring/command/downtime/schedule')) {
            $this->assembleAction(
                'scheduleDowntime',
                t('Downtime'),
                'icon-plug',
                t('Schedule a downtime to suppress all problem notifications within a specific period of time')
            );
        }

        if (
            $this->getAuth()->hasPermission('monitoring/command/schedule-check')
            || (
                $this->summary->$activeChecks > 0
                && $this->getAuth()->hasPermission('monitoring/command/schedule-check/active-only')
            )
        ) {
            $this->assembleAction(
                'scheduleCheck',
                t('Reschedule'),
                'icon-calendar-empty',
                t('Schedule the next active check at a different time than the current one')
            );
        }

        if ($this->getAuth()->hasPermission('monitoring/command/process-check-result')) {
            $this->assembleAction(
                'processCheckresult',
                t('Process check result'),
                'icon-edit',
                t('Submit passive check result')
            );
        }
    }

    protected function assembleAction($action, $label, $icon, $title)
    {
        $link = Html::tag(
            'a',
            [
                'href'                => $this->getLink($action),
                'class'               => 'action-link',
                'title'               => $title,
                'data-icinga-modal'   => true,
                'data-no-icinga-ajax' => true
            ],
            [
                Html::tag('i', ['class' => $icon]),
                $label
            ]
        );

        $this->add(Html::tag('li', $link));
    }

    protected function getLink($action)
    {
        return Url::fromPath("icingadb/{$this->type}s/$action")
            ->setQueryString(QueryString::render($this->getBaseFilter()))
            ->getAbsoluteUrl();
    }
}
