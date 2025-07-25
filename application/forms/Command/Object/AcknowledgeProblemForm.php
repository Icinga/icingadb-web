<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use CallbackFilterIterator;
use DateInterval;
use DateTime;
use Icinga\Application\Config;
use Icinga\Module\Icingadb\Command\Object\AcknowledgeProblemCommand;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Web\Notification;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Orm\Model;
use ipl\Validator\CallbackValidator;
use ipl\Web\FormDecorator\IcingaFormDecorator;
use ipl\Web\Widget\Icon;
use Iterator;
use LimitIterator;
use NoRewindIterator;
use Traversable;

use function ipl\Stdlib\iterable_value_first;

class AcknowledgeProblemForm extends CommandForm
{
    public function __construct()
    {
        $this->on(self::ON_SUCCESS, function () {
            if ($this->errorOccurred) {
                return;
            }

            $countObjects = count($this->getObjects());
            if (iterable_value_first($this->getObjects()) instanceof Host) {
                $message = sprintf(tp(
                    'Acknowledged problem successfully',
                    'Acknowledged problem on %d hosts successfully',
                    $countObjects
                ), $countObjects);
            } else {
                $message = sprintf(tp(
                    'Acknowledged problem successfully',
                    'Acknowledged problem on %d services successfully',
                    $countObjects
                ), $countObjects);
            }

            Notification::success($message);
        });
    }

    protected function assembleElements()
    {
        $this->addHtml(new HtmlElement(
            'div',
            Attributes::create(['class' => 'form-description']),
            new Icon('info-circle', ['class' => 'form-description-icon']),
            new HtmlElement(
                'ul',
                null,
                new HtmlElement('li', null, Text::create(t(
                    'This command is used to acknowledge host or service problems. When a problem is acknowledged,'
                    . ' future notifications about problems are temporarily disabled until the host or service'
                    . ' recovers.'
                )))
            )
        ));

        $config = Config::module('icingadb');
        $decorator = new IcingaFormDecorator();

        $this->addElement(
            'textarea',
            'comment',
            [
                'required'      => true,
                'label'         => t('Comment'),
                'description'   => t(
                    'If you work with other administrators, you may find it useful to share information about'
                    . ' the host or service that is having problems. Make sure you enter a brief description of'
                    . ' what you are doing.'
                ),
                'value'         => $config->get('settings', 'acknowledge_comment_text')
            ]
        );
        $decorator->decorate($this->getElement('comment'));

        $this->addElement(
            'checkbox',
            'persistent',
            [
                'label'         => t('Persistent Comment'),
                'value'         => (bool) $config->get('settings', 'acknowledge_persistent', false),
                'description'   => t(
                    'If you want the comment to remain even when the acknowledgement is removed, check this'
                    . ' option.'
                )
            ]
        );
        $decorator->decorate($this->getElement('persistent'));

        $this->addElement(
            'checkbox',
            'notify',
            [
                'label'         => t('Send Notification'),
                'value'         => (bool) $config->get('settings', 'acknowledge_notify', true),
                'description'   => t(
                    'If you want an acknowledgement notification to be sent out to the appropriate contacts,'
                    . ' check this option.'
                )
            ]
        );
        $decorator->decorate($this->getElement('notify'));

        $this->addElement(
            'checkbox',
            'sticky',
            [
                'label'         => t('Sticky Acknowledgement'),
                'value'         => (bool) $config->get('settings', 'acknowledge_sticky', false),
                'description'   => t(
                    'If you want the acknowledgement to remain until the host or service recovers even if the host'
                    . ' or service changes state, check this option.'
                )
            ]
        );
        $decorator->decorate($this->getElement('sticky'));

        $this->addElement(
            'checkbox',
            'expire',
            [
                'ignore'        => true,
                'class'         => 'autosubmit',
                'value'         => (bool) $config->get('settings', 'acknowledge_expire', false),
                'label'         => t('Use Expire Time'),
                'description'   => t('If the acknowledgement should expire, check this option.')
            ]
        );
        $decorator->decorate($this->getElement('expire'));

        if ($this->getElement('expire')->isChecked()) {
            $expireTime = new DateTime();
            $expireTime->add(new DateInterval($config->get('settings', 'acknowledge_expire_time', 'PT1H')));

            $this->addElement(
                'localDateTime',
                'expire_time',
                [
                    'data-use-datetime-picker'  => true,
                    'required'                  => true,
                    'value'                     => $expireTime,
                    'label'                     => t('Expire Time'),
                    'description'               => t(
                        'Choose the date and time when Icinga should delete the acknowledgement.'
                    ),
                    'validators'                => [
                        'DateTime' => ['break_chain_on_failure' => true],
                        'Callback' => function ($value, $validator) {
                            /** @var CallbackValidator $validator */
                            if ($value <= (new DateTime())) {
                                $validator->addMessage(t('The expire time must not be in the past'));
                                return false;
                            }

                            return true;
                        }
                    ]
                ]
            );
            $decorator->decorate($this->getElement('expire_time'));
        }
    }

    protected function assembleSubmitButton()
    {
        $this->addElement(
            'submit',
            'btn_submit',
            [
                'required'              => true,
                'label'                 => tp(
                    'Acknowledge problem',
                    'Acknowledge problems',
                    count($this->getObjects())
                ),
                'data-progress-label'   => tp(
                    'Acknowledging problem',
                    'Acknowledging problems',
                    count($this->getObjects())
                )
            ]
        );

        (new IcingaFormDecorator())->decorate($this->getElement('btn_submit'));
    }

    protected function getCommands(Iterator $objects): Traversable
    {
        $granted = new CallbackFilterIterator($objects, function (Model $object): bool {
            return $this->isGrantedOn('icingadb/command/acknowledge-problem', $object);
        });

        $command = new AcknowledgeProblemCommand();
        $command->setComment($this->getValue('comment'));
        $command->setAuthor($this->getAuth()->getUser()->getUsername());
        $command->setNotify($this->getElement('notify')->isChecked());
        $command->setSticky($this->getElement('sticky')->isChecked());
        $command->setPersistent($this->getElement('persistent')->isChecked());

        if (($expireTime = $this->getValue('expire_time')) !== null) {
            /** @var DateTime $expireTime */
            $command->setExpireTime($expireTime->getTimestamp());
        }

        $granted->rewind(); // Forwards the pointer to the first element
        while ($granted->valid()) {
            // Chunk objects to avoid timeouts with large sets
            yield $command->setObjects(new LimitIterator(new NoRewindIterator($granted), 0, 250));
        }
    }
}
