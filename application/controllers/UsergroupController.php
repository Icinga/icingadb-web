<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Model\Usergroup;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\UsergroupDetail;
use Icinga\Module\Icingadb\Widget\ItemList\UsergroupList;

class UsergroupController extends Controller
{
    /** @var Usergroup The usergroup object */
    protected $usergroup;

    public function init()
    {
        $this->assertRouteAccess('usergroups');

        $this->setTitle(t('User Group'));

        $name = $this->params->getRequired('name');

        $query = Usergroup::on($this->getDb());
        $query->getSelectBase()
            ->where(['usergroup.name = ?' => $name]);

        $this->applyRestrictions($query);

        $usergroup = $query->first();
        if ($usergroup === null) {
            throw new NotFoundError(t('User group not found'));
        }

        $this->usergroup = $usergroup;
        $this->view->title = $usergroup->display_name;
    }

    public function indexAction()
    {
        $this->addControl((new UsergroupList([$this->usergroup]))->setNoSubjectLink()->setDetailActionsDisabled());
        $this->addContent(new UsergroupDetail($this->usergroup));

        $this->setAutorefreshInterval(10);
    }
}
