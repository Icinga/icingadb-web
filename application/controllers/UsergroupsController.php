<?php

namespace Icinga\Module\Eagle\Controllers;

use Icinga\Module\Eagle\Model\Usergroup;
use Icinga\Module\Eagle\Web\Controller;
use Icinga\Module\Eagle\Widget\ItemList\UsergroupList;

class UsergroupsController extends Controller
{
    public function indexAction()
    {
        $this->setTitle($this->translate('User Groups'));

        $db = $this->getDb();

        $usergroups = Usergroup::on($db)->with('user');

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($usergroups);
        $filterControl = $this->createFilterControl($usergroups);

        $this->filter($usergroups);

        yield $this->export($usergroups);

        $this->addControl($paginationControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $this->addContent(new UsergroupList($usergroups));
    }
}
