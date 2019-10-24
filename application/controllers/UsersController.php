<?php

namespace Icinga\Module\Eagle\Controllers;

use Icinga\Module\Eagle\Model\User;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\ItemList\UserList;

class UsersController extends Controller
{
    public function indexAction()
    {
        $this->setTitle($this->translate('Users'));

        $db = $this->getDb();

        $users = User::on($db);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($users);
        $filterControl = $this->createFilterControl($users);

        $this->filter($users);

        yield $this->export($users);

        $userList = new UserList($users);

        $this->addControl($paginationControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $this->addContent($userList);
    }
}
