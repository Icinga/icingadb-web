<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Model\Usergroup;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
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

        $this->handleSearchRequest($usergroups);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($usergroups);
        $sortControl = $this->createSortControl(
            $usergroups,
            [
                'usergroup.display_name' => t('Name')
            ]
        );
        $searchBar = $this->createSearchBar($usergroups, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam()
        ]);

        $this->filter($usergroups, $searchBar->getFilter());

        yield $this->export($usergroups);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($searchBar);

        $this->addContent(new UsergroupList($usergroups));

        if ($searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }

        $this->setAutorefreshInterval(10);
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(Usergroup::class);
        $suggestions->forRequest(ServerRequest::fromGlobals());
        $this->getDocument()->add($suggestions);
    }
}
