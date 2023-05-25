<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\UserDetail;
use Icinga\Module\Icingadb\Widget\ItemTable\UserTableRow;
use ipl\Stdlib\Filter;

class UserController extends Controller
{
    /** @var User The user object */
    protected $user;

    public function init()
    {
        $this->assertRouteAccess('users');

        $this->addTitleTab(t('User'));

        $name = $this->params->getRequired('name');

        $query = User::on($this->getDb());
        $query->filter(Filter::equal('user.name', $name));

        $this->applyRestrictions($query);

        $user = $query->first();
        if ($user === null) {
            throw new NotFoundError(t('User not found'));
        }

        $this->user = $user;
        $this->setTitle($user->display_name);
    }

    public function indexAction()
    {
        $this->addControl(new UserTableRow($this->user));
        $this->addContent(new UserDetail($this->user));

        $this->setAutorefreshInterval(10);
    }
}
