<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Notifications;

use Icinga\Web\Notification;
use Icinga\Web\Session;
use ipl\Html\Form;
use ipl\I18n\Translation;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Widget\Icon;

class SubscriptionForm extends Form
{
    use CsrfCounterMeasure;
    use Translation;

    protected $defaultAttributes = ['class' => 'inline'];

    /** @var bool Whether to subscribe (true) or unsubscribe (false) the current user */
    protected bool $subscribe;

    /** @var int The count of objects that are being subscribed/unsubscribed */
    protected int $count = 0;

    /** @var ?array Attributes of the submit button while the role change of this form is pending */
    protected ?array $pendingJobAttributes = null;

    /**
     * Create a new SubscriptionForm
     *
     * @param bool $subscribe Whether the form should subscribe or unsubscribe on submit
     */
    public function __construct(bool $subscribe)
    {
        $this->subscribe = $subscribe;

        $this->setAttribute(
            'title',
            $subscribe
                ? $this->translate('Receive notifications about this problem')
                : $this->translate('Stop receiving notifications about this problem')
        );

        $this->on(static::ON_SUBMIT, function () {
            Notification::success(
                sprintf(
                    $this->subscribe
                        ? $this->translatePlural(
                            'Subscribed to the problem successfully',
                            'Subscribed to %d problems successfully',
                            $this->count
                        )
                        : $this->translatePlural(
                            'Unsubscribed from the problem successfully',
                            'Unsubscribed from %d problems successfully',
                            $this->count
                        ),
                    $this->count
                )
            );
        });
    }

    /**
     * Set the count of objects that are being subscribed/unsubscribed
     *
     * @param int $count
     *
     * @return $this
     */
    public function setObjectCount(int $count): static
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Mark the form as waiting for the role change it requested
     *
     * @param array $attributes The attributes of the pending job
     *                          {@see \Icinga\Module\Notifications\Integrations\V1\Incident::getPendingJobAttributes()}
     *
     * @return $this
     */
    public function setPendingJob(array $attributes): static
    {
        $this->pendingJobAttributes = $attributes;

        return $this;
    }

    protected function assemble(): void
    {
        $this->addCsrfCounterMeasure(Session::getSession()->getId());
        $this->addElement(
            'submitButton',
            'btn_submit',
            [
                'class' => ['link-button', 'spinner'],
                'label' => $this->subscribe
                    ? [new Icon('share'), $this->translate('Subscribe')]
                    : [new Icon('bell-slash'), $this->translate('Unsubscribe')]
            ]
        );

        if ($this->pendingJobAttributes !== null) {
            $this->getElement('btn_submit')->getAttributes()
                ->add('class', 'active')
                ->set($this->pendingJobAttributes);
        }
    }
}
