<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use DateInterval;
use DateTime;
use Icinga\Application\Config;
use Icinga\Module\Icingadb\Command\Object\AddCommentCommand;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Web\Notification;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Validator\CallbackValidator;
use ipl\Web\FormDecorator\IcingaFormDecorator;
use ipl\Web\Widget\Icon;
use Traversable;

class AddCommentForm extends CommandForm
{
    public function __construct()
    {
        $this->on(self::ON_SUCCESS, function () {
            if ($this->errorOccurred) {
                return;
            }

            $countObjects = count($this->getObjects());
            if (current($this->getObjects()) instanceof Host) {
                $message = sprintf(
                    tp('Added comment successfully', 'Added comment to %d hosts successfully', $countObjects),
                    $countObjects
                );
            } else {
                $message = sprintf(
                    tp('Added comment successfully', 'Added comment to %d services successfully', $countObjects),
                    $countObjects
                );
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
                new HtmlElement(
                    'li',
                    null,
                    Text::create(t('This command is used to add host or service comments.'))
                )
            )
        ));

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
                )
            ]
        );
        $decorator->decorate($this->getElement('comment'));

        $config = Config::module('icingadb');

        $this->addElement(
            'checkbox',
            'expire',
            [
                'ignore'        => true,
                'class'         => 'autosubmit',
                'value'         => (bool) $config->get('settings', 'comment_expire', false),
                'label'         => t('Use Expire Time'),
                'description'   => t('If the comment should expire, check this option.')
            ]
        );
        $decorator->decorate($this->getElement('expire'));

        if ($this->getElement('expire')->isChecked()) {
            $expireTime = new DateTime();
            $expireTime->add(new DateInterval($config->get('settings', 'comment_expire_time', 'PT1H')));

            $this->addElement(
                'localDateTime',
                'expire_time',
                [
                    'data-use-datetime-picker'  => true,
                    'required'                  => true,
                    'value'                     => $expireTime,
                    'label'                     => t('Expire Time'),
                    'description'               => t('Choose the date and time when Icinga should delete the comment.'),
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
                'required'  => true,
                'label'     => tp('Add comment', 'Add comments', count($this->getObjects()))
            ]
        );

        (new IcingaFormDecorator())->decorate($this->getElement('btn_submit'));
    }

    protected function getCommands(Traversable $objects): Traversable
    {
        $granted = $this->filterGrantedOn('icingadb/command/comment/add', $objects);

        if ($granted->valid()) {
            $command = new AddCommentCommand();
            $command->setObjects($granted);
            $command->setComment($this->getValue('comment'));
            $command->setAuthor($this->getAuth()->getUser()->getUsername());

            if (($expireTime = $this->getValue('expire_time'))) {
                /** @var DateTime $expireTime */
                $command->setExpireTime($expireTime->getTimestamp());
            }

            yield $command;
        }
    }
}
