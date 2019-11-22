<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Model\Usergroup;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\UsergroupList;
use Icinga\Module\Icingadb\Widget\ItemList\UserList;
use ipl\Html\Html;

class UsergroupController extends Controller
{
    /** @var Usergroup The usergroup object */
    protected $usergroup;

    public function init()
    {
        $this->setTitle($this->translate('User Group'));

        $name = $this->params->shiftRequired('name');

        $query = Usergroup::on($this->getDb());
        $query->getSelectBase()
            ->where(['name = ?' => $name]);

        $this->applyMonitoringRestriction($query);

        $usergroup = $query->first();
        if ($usergroup === null) {
            throw new NotFoundError($this->translate('User group not found'));
        }

        $this->usergroup = $usergroup;
    }

    public function indexAction()
    {
        $this->addControl(new UsergroupList([$this->usergroup]));

        $this->addContent(new UserList($this->usergroup->user));
    }
}
