<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\UserList;
use ipl\Html\Html;

class UserController extends Controller
{
    /** @var User The user object */
    protected $user;

    public function init()
    {
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

        $this->addContent(Html::tag('h2', 'Details'));
        $this->addContent(Html::tag('ul', ['class' => 'key-value-list'], [
            Html::tag('li', [
                Html::tag('span', ['class' => 'label'], 'E-Mail'),
                Html::tag(
                    'span',
                    ['class' => 'value'],
                    $this->user->email ?: Html::tag('span', ['class' => 'text-muted'], 'Unset')
                )
            ]),
            Html::tag('li', [
                Html::tag('span', ['class' => 'label'], 'Pager'),
                Html::tag(
                    'span',
                    ['class' => 'value'],
                    $this->user->pager ?: Html::tag('span', ['class' => 'text-muted'], 'Unset')
                )
            ])
        ]));
    }
}
