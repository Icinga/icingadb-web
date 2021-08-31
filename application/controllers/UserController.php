<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\UserDetail;
use Icinga\Module\Icingadb\Widget\ItemList\UserList;

class UserController extends Controller
{
    /** @var User The user object */
    protected $user;

    public function init()
    {
        $this->assertRouteAccess('users');

        $this->setTitle(t('User'));

        $name = $this->params->getRequired('name');

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
        $this->addContent(new UserDetail($this->user));

        $this->setAutorefreshInterval(10);
    }
}
