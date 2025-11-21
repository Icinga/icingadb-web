<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\ObjectHeader;
use Icinga\Module\Icingadb\Widget\Detail\UserDetail;
use ipl\Stdlib\Filter;

class ContactController extends Controller
{
    /** @var User The user object */
    protected $user;

    public function init()
    {
        $this->assertRouteAccess('contacts');

        $this->addTitleTab(t('Contact'));

        $name = $this->params->getRequired('name');

        $query = User::on($this->getDb())->with('timeperiod');
        $query->filter(Filter::equal('user.name', $name));

        $this->applyRestrictions($query);

        $user = $query->first();
        if ($user === null) {
            throw new NotFoundError(t('Contact not found'));
        }

        $this->user = $user;
        $this->setTitle($user->display_name);
    }

    public function indexAction()
    {
        $this->addControl(new ObjectHeader($this->user));
        $this->addContent(new UserDetail($this->user));

        $this->setAutorefreshInterval(10);
    }
}
