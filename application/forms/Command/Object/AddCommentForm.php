<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use DateInterval;
use DateTime;
use Icinga\Application\Config;
use Icinga\Module\Icingadb\Command\Object\AddCommentCommand;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use ipl\Html\HtmlElement;
use ipl\Orm\Model;
use ipl\Web\FormDecorator\IcingaFormDecorator;
use ipl\Web\Widget\Icon;

class AddCommentForm extends CommandForm
{
    use Auth;

    protected function assembleElements()
    {
        $this->add(new HtmlElement('div', ['class' => 'form-description'], [
            new Icon('info-circle', ['class' => 'form-description-icon']),
            new HtmlElement('ul', null, [
                new HtmlElement('li', null, t('This command is used to add host or service comments.'))
            ])
        ]));

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
        $commentExpire = (bool) $config->get('settings', 'comment_expire', false);

        $this->addElement(
            'checkbox',
            'expire',
            [
                'ignore'        => true,
                'class'         => 'autosubmit',
                'value'         => $commentExpire,
                'label'         => t('Use Expire Time'),
                'description'   => t('If the comment should expire, check this option.')
            ]
        );
        $decorator->decorate($this->getElement('expire'));

        if ($commentExpire || $this->getPopulatedValue('expire') === 'y') {
            $expireTime = new DateTime();
            $expireTime->add(new DateInterval($config->get('settings', 'comment_expire_time', 'PT1H')));

            $this->addElement(
                'localDateTime',
                'expire_time',
                [
                    'required'      => true,
                    'value'         => $expireTime,
                    'label'         => t('Expire Time'),
                    'description'   => t('Choose the date and time when Icinga should delete the comment.')
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

    protected function getCommand(Model $object)
    {
        if (! $this->isGrantedOn('monitoring/command/comment/add', $object)) {
            return null;
        }

        $command = new AddCommentCommand();
        $command->setObject($object);
        $command->setComment($this->getValue('comment'));
        $command->setAuthor($this->getAuth()->getUser()->getUsername());

        if (($expireTime = $this->getValue('expire_time'))) {
            /** @var DateTime $expireTime */
            $command->setExpireTime($expireTime->getTimestamp());
        }

        return $command;
    }
}
