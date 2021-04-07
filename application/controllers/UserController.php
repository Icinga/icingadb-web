<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

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
        $this->assertRouteAccess('users');

        $this->setTitle(t('User'));

        $name = $this->params->shiftRequired('name');

        $query = User::on($this->getDb());
        $query->getSelectBase()
            ->where(['user.name = ?' => $name]);

        $this->applyRestrictions($query);

        $user = $query->first();
        if ($user === null) {
            throw new NotFoundError(t('User not found'));
        }

        $this->user = $user;
    }

    public function indexAction()
    {
        $this->addControl((new UserList([$this->user]))->setNoSubjectLink()->setDetailActionsDisabled());

        $this->addContent(Html::tag('h2', t('Details')));
        $this->addContent(Html::tag('ul', ['class' => 'key-value-list'], [
            Html::tag('li', [
                Html::tag('span', ['class' => 'label'], t('E-Mail')),
                Html::tag(
                    'span',
                    ['class' => 'value'],
                    $this->user->email ?: Html::tag('span', ['class' => 'text-muted'], t('Unset'))
                )
            ]),
            Html::tag('li', [
                Html::tag('span', ['class' => 'label'], t('Pager')),
                Html::tag(
                    'span',
                    ['class' => 'value'],
                    $this->user->pager ?: Html::tag('span', ['class' => 'text-muted'], t('Unset'))
                )
            ])
        ]));

        $this->setAutorefreshInterval(10);
    }
}
