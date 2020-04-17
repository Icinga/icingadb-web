<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\UserList;
use Icinga\Security\SecurityException;
use ipl\Html\Html;

class UserController extends Controller
{
    /** @var User The user object */
    protected $user;

    public function init()
    {
        if (! $this->hasPermission('*') && $this->hasPermission('no-monitoring/contacts')) {
            throw new SecurityException($this->translate('No permission for %s'), 'monitoring/contacts');
        }

        $this->setTitle($this->translate('User'));

        $name = $this->params->shiftRequired('name');

        $query = User::on($this->getDb());
        $query->getSelectBase()
            ->where(['user.name = ?' => $name]);

        $this->applyMonitoringRestriction($query);

        $user = $query->first();
        if ($user === null) {
            throw new NotFoundError($this->translate('User not found'));
        }

        $this->user = $user;
    }

    public function indexAction()
    {
        $this->addControl(new UserList([$this->user]));

        $this->addContent(Html::tag('h2', $this->translate('Details')));
        $this->addContent(Html::tag('ul', ['class' => 'key-value-list'], [
            Html::tag('li', [
                Html::tag('span', ['class' => 'label'], $this->translate('E-Mail')),
                Html::tag(
                    'span',
                    ['class' => 'value'],
                    $this->user->email ?: Html::tag('span', ['class' => 'text-muted'], $this->translate('Unset'))
                )
            ]),
            Html::tag('li', [
                Html::tag('span', ['class' => 'label'], $this->translate('Pager')),
                Html::tag(
                    'span',
                    ['class' => 'value'],
                    $this->user->pager ?: Html::tag('span', ['class' => 'text-muted'], $this->translate('Unset'))
                )
            ])
        ]));

        $this->setAutorefreshInterval(10);
    }
}
