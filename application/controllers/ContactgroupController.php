<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Icingadb\Common\EmbeddedList;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Model\Usergroup;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\ObjectHeader;
use Icinga\Module\Icingadb\Widget\Detail\UsergroupDetail;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;

class ContactgroupController extends Controller
{
    use EmbeddedList;

    /** @var Usergroup The usergroup object */
    protected $usergroup;

    public function init()
    {
        $this->assertRouteAccess('contactgroups');

        $this->addTitleTab(t('Contact Group'));

        $name = $this->params->shiftRequired('name');

        $query = Usergroup::on($this->getDb());
        $query->filter(Filter::equal('usergroup.name', $name));

        $this->applyRestrictions($query);

        $usergroup = $query->first();
        if ($usergroup === null) {
            throw new NotFoundError(t('Contact group not found'));
        }

        $this->usergroup = $usergroup;
        $this->setTitle($usergroup->display_name);
    }

    public function indexAction()
    {
        $this->setAutorefreshInterval(10);

        $users = User::on($this->getDb())
            ->filter(Filter::equal('usergroup.id', $this->usergroup->id));
        $this->applyRestrictions($users);

        $list = $this->createEmbeddedList($users, $this->translate('Contacts'), [
            'user.display_name' => $this->translate('Name'),
            'user.email'        => $this->translate('Email'),
            'user.pager'        => $this->translate('Pager Address / Number'),
        ], ['name']);
        if ($list === null) {
            $this->sendMultipartUpdate();

            return;
        }

        $this->addControl(new ObjectHeader($this->usergroup));
        $this->addContent(new UsergroupDetail($this->usergroup, $list));
    }

    /**
     * Show filter suggestions for the group members
     *
     * @return void
     */
    public function completeAction(): void
    {
        $suggestions = (new ObjectSuggestions())
            ->setModel(User::class)
            ->onlyWithCustomVarSources(['user'])
            ->setBaseFilter(Filter::equal('usergroup.name', $this->usergroup->name))
            ->forRequest($this->getServerRequest());

        $this->getDocument()->add($suggestions);
    }

    /**
     * Show the filter editor for the group members
     *
     * @return void
     */
    public function searchEditorAction(): void
    {
        $editor = $this->createSearchEditor(User::on($this->getDb()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM,
            'name',
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle($this->translate('Adjust Filter'));
    }
}
