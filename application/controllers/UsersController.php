<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
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

        $this->handleSearchRequest($users);

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
        $searchBar = $this->createSearchBar($users, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam()
        ]);

        $this->filter($users, $searchBar->getFilter());

        yield $this->export($users);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($searchBar);

        $this->addContent(new UserList($users));

        if ($searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }

        $this->setAutorefreshInterval(10);
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(User::class);
        $suggestions->forRequest(ServerRequest::fromGlobals());
        $this->getDocument()->add($suggestions);
    }
}
