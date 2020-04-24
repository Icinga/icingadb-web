<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\Usergroup;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\ItemList\UsergroupList;
use Icinga\Security\SecurityException;

class UsergroupsController extends Controller
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
        $this->setTitle(t('User Groups'));

        $db = $this->getDb();

        $usergroups = Usergroup::on($db)->with('user');

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($usergroups);
        $sortControl = $this->createSortControl(
            $usergroups,
            [
                'usergroup.display_name' => t('Name')
            ]
        );
        $filterControl = $this->createFilterControl($usergroups);

        $this->filter($usergroups);

        yield $this->export($usergroups);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($filterControl);

        $this->addContent(new UsergroupList($usergroups));

        $this->setAutorefreshInterval(10);
    }
}
