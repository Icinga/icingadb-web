<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\UserList;
use Icinga\Security\SecurityException;

class UsersController extends Controller
{
    public function init()
    {
        parent::init();

        if (! $this->hasPermission('*') && $this->hasPermission('no-monitoring/contacts')) {
            throw new SecurityException(t('No permission for %s'), 'monitoring/contacts');
        }
    }

    public function indexAction()
    {
        $this->setTitle(t('Users'));

        $db = $this->getDb();

        $users = User::on($db);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($users);
        $sortControl = $this->createSortControl(
            $users,
            [
                'user.display_name' => t('Name'),
                'user.email'        => t('Email'),
                'user.pager'        => t('Pager Address / Number')
            ]
        );
        $filterControl = $this->createFilterControl($users);

        $this->filter($users);

        yield $this->export($users);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $this->addContent(new UserList($users));

        $this->setAutorefreshInterval(10);
    }
}
