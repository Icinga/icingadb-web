<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Forms\Command\Object\CheckNowForm;
use Icinga\Module\Icingadb\Forms\Command\Object\RemoveAcknowledgementForm;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Stdlib\BaseFilter;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

class MultiselectQuickActions extends BaseHtmlElement
{
    use BaseFilter;
    use Auth;

    protected $summary;

    protected $type;

    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'quick-actions'];

    /** @var bool Whether to allow process check results */
    protected $allowToProcessCheckResults = true;

    /** @var ?string The summary column prefix */
    protected $columnPrefix;

    /** @var ?string The url path for {@see getLink()} method (default: `icingadb/$this->type . 's'`) */
    protected $urlPath;

    public function __construct($type, $summary)
    {
        $this->summary = $summary;
        $this->type = $type;
    }

    /**
     * Set the summary column prefix
     *
     * @param string $columnPrefix
     *
     * @return $this
     */
    public function setColumnPrefix(string $columnPrefix): self
    {
        $this->columnPrefix = $columnPrefix;

        return $this;
    }

    /**
     * Get the summary column prefix (default:  `$this->type . 's'`)
     *
     * @return string
     */
    public function getColumnPrefix(): string
    {
        if ($this->columnPrefix === null) {
            $this->columnPrefix = $this->type . 's';
        }

        return $this->columnPrefix;
    }

    /**
     * Set the url path for {@see getLink()} method
     *
     * Omits the trailing slashes
     *
     * @param string $urlPath
     *
     * @return $this
     */
    public function setUrlPath(string $urlPath): self
    {
        $this->urlPath = rtrim($urlPath, '/');

        return $this;
    }

    /**
     * Get the url path for {@see getLink()} method
     *
     * If not set `icingadb/$this->type . 's'` is used
     *
     * @return string
     */
    public function getUrlPath(): string
    {
        if ($this->urlPath === null) {
            $this->urlPath = "icingadb/{$this->type}s";
        }

        return $this->urlPath;
    }

    /**
     * Set whether to allow process check results
     *
     * @param bool $state
     *
     * @return $this
     */
    public function setAllowToProcessCheckResults(bool $state = true): self
    {
        $this->allowToProcessCheckResults = $state;

        return $this;
    }

    protected function assemble()
    {
        $unacknowledged = "{$this->getColumnPrefix()}_problems_unacknowledged";
        $acks = "{$this->getColumnPrefix()}_acknowledged";
        $activeChecks = "{$this->getColumnPrefix()}_active_checks_enabled";
        $passiveChecks = "{$this->getColumnPrefix()}_passive_checks_enabled";

        if (
            $this->summary->$unacknowledged > $this->summary->$acks
            && $this->isGrantedOnType(
                'icingadb/command/acknowledge-problem',
                $this->type,
                $this->getBaseFilter(),
                false
            )
        ) {
            $this->assembleAction(
                'acknowledge',
                t('Acknowledge'),
                'check-circle',
                t('Acknowledge this problem, suppress all future notifications for it and tag it as being handled')
            );
        }

        if (
            $this->summary->$acks > 0
            && $this->isGrantedOnType(
                'icingadb/command/remove-acknowledgement',
                $this->type,
                $this->getBaseFilter(),
                false
            )
        ) {
            $removeAckForm = (new RemoveAcknowledgementForm())
                ->setAction($this->getLink('removeAcknowledgement'))
                // TODO: This is a hack as for the button label the count of objects is used. setCount? setMultiple?
                ->setObjects(array_fill(0, $this->summary->$acks, null));

            $this->add(Html::tag('li', $removeAckForm));
        }

        if (
            $this->isGrantedOnType('icingadb/command/schedule-check', $this->type, $this->getBaseFilter(), false)
            || (
                ! empty($this->summary->$activeChecks)
                && $this->isGrantedOnType(
                    'icingadb/command/schedule-check/active-only',
                    $this->type,
                    $this->getBaseFilter(),
                    false
                )
            )
        ) {
            $this->add(Html::tag('li', (new CheckNowForm())->setAction($this->getLink('checkNow'))));
        }

        if ($this->isGrantedOnType('icingadb/command/comment/add', $this->type, $this->getBaseFilter(), false)) {
            $this->assembleAction(
                'addComment',
                t('Comment'),
                'comment',
                t('Add a new comment')
            );
        }

        if (
            $this->isGrantedOnType(
                'icingadb/command/send-custom-notification',
                $this->type,
                $this->getBaseFilter(),
                false
            )
        ) {
            $this->assembleAction(
                'sendCustomNotification',
                t('Notification'),
                'bell',
                t('Send a custom notification')
            );
        }

        if (
            $this->isGrantedOnType(
                'icingadb/command/downtime/schedule',
                $this->type,
                $this->getBaseFilter(),
                false
            )
        ) {
            $this->assembleAction(
                'scheduleDowntime',
                t('Downtime'),
                'plug',
                t('Schedule a downtime to suppress all problem notifications within a specific period of time')
            );
        }

        if (
            $this->isGrantedOnType('icingadb/command/schedule-check', $this->type, $this->getBaseFilter(), false)
            || (
                ! empty($this->summary->$activeChecks)
                && $this->isGrantedOnType(
                    'icingadb/command/schedule-check/active-only',
                    $this->type,
                    $this->getBaseFilter(),
                    false
                )
            )
        ) {
            $this->assembleAction(
                'scheduleCheck',
                t('Reschedule'),
                'calendar',
                t('Schedule the next active check at a different time than the current one')
            );
        }

        if (
            $this->allowToProcessCheckResults
            && $this->summary->$passiveChecks > 0
            && $this->isGrantedOnType(
                'icingadb/command/process-check-result',
                $this->type,
                $this->getBaseFilter(),
                false
            )
        ) {
            $this->assembleAction(
                'processCheckresult',
                t('Process check result'),
                'edit',
                t('Submit passive check result')
            );
        }
    }

    protected function assembleAction(string $action, string $label, string $icon, string $title)
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
                new Icon($icon),
                $label
            ]
        );

        $this->add(Html::tag('li', $link));
    }

    protected function getLink(string $action): string
    {
        return Url::fromPath($this->getUrlPath() . '/' . $action)
            ->setFilter($this->getBaseFilter())
            ->getAbsoluteUrl();
    }
}
